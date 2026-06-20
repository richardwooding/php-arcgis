<?php

declare(strict_types=1);

namespace RichardWooding\ArcGis\Tests;

use PHPUnit\Framework\TestCase;
use RichardWooding\ArcGis\Client;
use RichardWooding\ArcGis\Exception\ApiException;
use RichardWooding\ArcGis\Exception\TransportException;
use RichardWooding\ArcGis\Http\Request;
use RichardWooding\ArcGis\Http\Response;
use RichardWooding\ArcGis\OutputFormat;
use RichardWooding\ArcGis\QueryParams;
use RichardWooding\ArcGis\Tests\Support\MockHttpClient;

final class ClientTest extends TestCase
{
    public function testQueryEsriJson(): void
    {
        $http = MockHttpClient::withBody(<<<'JSON'
            {
                "objectIdFieldName": "OBJECTID",
                "exceededTransferLimit": false,
                "features": [
                    {"geometry": {"x": 1, "y": 2}, "attributes": {"NAME": "Block A", "STAGE": 4}}
                ]
            }
            JSON);

        $client = new Client('https://example.test', httpClient: $http);
        $fs = $client->query(new QueryParams(
            layerId: 7,
            where: 'STAGE = 4',
            format: OutputFormat::Json,
        ));

        self::assertSame('STAGE = 4', $http->lastParam('where'));
        self::assertSame('json', $http->lastParam('f'));
        self::assertCount(1, $fs->features);
        self::assertSame('Block A', $fs->features[0]->attrs()['NAME']);
        self::assertSame('OBJECTID', $fs->objectIdFieldName);
    }

    public function testQueryGeoJsonProperties(): void
    {
        $http = MockHttpClient::withBody(<<<'JSON'
            {
                "type": "FeatureCollection",
                "features": [
                    {"type": "Feature", "geometry": null, "properties": {"NAME": "Ward 1"}}
                ]
            }
            JSON);

        $client = new Client('https://example.test', httpClient: $http);
        $fs = $client->query(new QueryParams(layerId: 1));

        self::assertSame('Ward 1', $fs->features[0]->attrs()['NAME']);
        self::assertSame('geojson', $http->lastParam('f'));
    }

    public function testQueryAllPaginates(): void
    {
        $http = new MockHttpClient(static function (Request $r): Response {
            parse_str((string) parse_url($r->url, PHP_URL_QUERY), $q);
            $offset = $q['resultOffset'] ?? '0';

            return new Response(200, $offset === '0'
                ? '{"exceededTransferLimit": true, "features": [{"attributes": {"id": 1}}, {"attributes": {"id": 2}}]}'
                : '{"exceededTransferLimit": false, "features": [{"attributes": {"id": 3}}]}');
        });

        $client = new Client('https://example.test', httpClient: $http);
        $all = $client->queryAll(new QueryParams(layerId: 7));

        self::assertCount(3, $all);
        self::assertSame(2, $http->calls);
        self::assertSame('2', $http->lastParam('resultOffset'));
    }

    public function testQueryCount(): void
    {
        $http = MockHttpClient::withBody('{"count": 42}');

        $client = new Client('https://example.test', httpClient: $http);
        $n = $client->queryCount(new QueryParams(layerId: 7));

        self::assertSame(42, $n);
        self::assertSame('true', $http->lastParam('returnCountOnly'));
        self::assertSame('json', $http->lastParam('f'));
    }

    public function testQueryIds(): void
    {
        $http = MockHttpClient::withBody('{"objectIds": [10, 20, 30]}');

        $client = new Client('https://example.test', httpClient: $http);
        $ids = $client->queryIds(new QueryParams(layerId: 7));

        self::assertSame([10, 20, 30], $ids);
        self::assertSame('true', $http->lastParam('returnIdsOnly'));
    }

    public function testServiceInfo(): void
    {
        $http = MockHttpClient::withBody(
            '{"serviceDescription": "Open Data", "layers": [{"id": 1, "name": "Wards"}], "tables": []}',
        );

        $client = new Client('https://example.test', httpClient: $http);
        $info = $client->serviceInfo();

        self::assertSame('Open Data', $info->serviceDescription);
        self::assertCount(1, $info->layers);
        self::assertSame('Wards', $info->layers[0]->name);
    }

    public function testLayerInfo(): void
    {
        $http = new MockHttpClient(static function (Request $r): Response {
            if (!str_starts_with((string) parse_url($r->url, PHP_URL_PATH), '/7')) {
                return new Response(404, 'wrong path');
            }

            return new Response(200, '{"id": 7, "name": "Blocks", "geometryType": "esriGeometryPolygon", "maxRecordCount": 2000, "supportsPagination": true}');
        });

        $client = new Client('https://example.test', httpClient: $http);
        $info = $client->layer(7)->info();

        self::assertSame('Blocks', $info->name);
        self::assertSame(2000, $info->maxRecordCount);
        self::assertTrue($info->supportsPagination);
    }

    public function testApiErrorOn200(): void
    {
        $http = MockHttpClient::withBody(
            '{"error": {"code": 400, "message": "Invalid query", "details": ["Unable to parse where clause"]}}',
        );

        $client = new Client('https://example.test', httpClient: $http);

        try {
            $client->query(new QueryParams(layerId: 7));
            self::fail('expected ApiException');
        } catch (ApiException $e) {
            self::assertSame(400, $e->getCode());
            self::assertSame('Invalid query', $e->getArcGisMessage());
            self::assertSame(['Unable to parse where clause'], $e->getDetails());
        }
    }

    public function testHttpErrorStatus(): void
    {
        $http = MockHttpClient::withBody('boom', 500);

        $client = new Client('https://example.test', httpClient: $http);

        $this->expectException(TransportException::class);
        $client->query(new QueryParams(layerId: 7));
    }

    public function testWithToken(): void
    {
        $http = MockHttpClient::withBody('{"features": []}');

        $client = new Client('https://example.test', httpClient: $http, token: 'secret-token');
        $client->query(new QueryParams(layerId: 7));

        self::assertSame('secret-token', $http->lastParam('token'));
    }

    public function testTokenAppliedToServiceInfo(): void
    {
        $http = MockHttpClient::withBody('{"serviceDescription": "x"}');

        $client = new Client('https://example.test', httpClient: $http, token: 't');
        $client->serviceInfo();

        self::assertSame('t', $http->lastParam('token'));
    }

    public function testLargeQueryFallsBackToPost(): void
    {
        $http = MockHttpClient::withBody('{"features": []}');

        // A polygon with enough vertices to exceed the URL-length threshold.
        $ring = [];
        for ($i = 0; $i < 400; ++$i) {
            $ring[] = [18.0 + $i / 1000, -34.0 - $i / 1000];
        }

        $client = new Client('https://example.test', httpClient: $http);
        $client->layer(7)->query()->withinPolygon([$ring])->first();

        self::assertNotNull($http->lastRequest);
        self::assertSame('POST', $http->lastRequest->method);
        self::assertNotNull($http->lastRequest->body);
        self::assertSame('esriGeometryPolygon', $http->lastParam('geometryType'));
    }
}

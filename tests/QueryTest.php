<?php

declare(strict_types=1);

namespace RichardWooding\ArcGis\Tests;

use PHPUnit\Framework\TestCase;
use RichardWooding\ArcGis\Client;
use RichardWooding\ArcGis\GeometryType;
use RichardWooding\ArcGis\OutputFormat;
use RichardWooding\ArcGis\QueryParams;
use RichardWooding\ArcGis\SpatialRel;
use RichardWooding\ArcGis\Tests\Support\MockHttpClient;

final class QueryTest extends TestCase
{
    public function testDefaultsApplied(): void
    {
        $p = new QueryParams(layerId: 7);
        $p->applyDefaults();

        self::assertSame('1=1', $p->where);
        self::assertSame(OutputFormat::GeoJson, $p->format);
        self::assertSame(1000, $p->pageSize);
        self::assertNull($p->geometryType);
        self::assertNull($p->spatialRel);
        self::assertSame(0, $p->inSR);
    }

    public function testGeometryFilterDefaults(): void
    {
        $p = new QueryParams(layerId: 7);
        $p->envelope = new \RichardWooding\ArcGis\Geometry\Envelope(18.4, -34.0, 18.6, -33.8);
        $p->applyDefaults();

        self::assertSame(GeometryType::Envelope, $p->geometryType);
        self::assertSame(SpatialRel::Intersects, $p->spatialRel);
        self::assertSame(4326, $p->inSR);
    }

    public function testValuesEnvelopeAndInSr(): void
    {
        $http = MockHttpClient::withBody('{"features": []}');
        $client = new Client('https://example.test', httpClient: $http);

        $client->layer(7)->query()
            ->withinEnvelope(18.4, -34.0, 18.6, -33.8)
            ->first();

        self::assertSame('18.4,-34,18.6,-33.8', $http->lastParam('geometry'));
        self::assertSame('esriGeometryEnvelope', $http->lastParam('geometryType'));
        self::assertSame('esriSpatialRelIntersects', $http->lastParam('spatialRel'));
        self::assertSame('4326', $http->lastParam('inSR'));
    }

    public function testPointFilterWithSpatialRelOverride(): void
    {
        $http = MockHttpClient::withBody('{"features": []}');
        $client = new Client('https://example.test', httpClient: $http);

        $client->layer(7)->query()
            ->intersectsPoint(18.42, -33.92)
            ->spatialRel(SpatialRel::Within)
            ->first();

        self::assertSame('18.42,-33.92', $http->lastParam('geometry'));
        self::assertSame('esriGeometryPoint', $http->lastParam('geometryType'));
        self::assertSame('esriSpatialRelWithin', $http->lastParam('spatialRel'));
    }

    public function testPolygonFilterEncoding(): void
    {
        $http = MockHttpClient::withBody('{"features": []}');
        $client = new Client('https://example.test', httpClient: $http);

        $client->layer(7)->query()
            ->withinPolygon([[[18.0, -34.0], [18.1, -34.0], [18.1, -34.1], [18.0, -34.0]]])
            ->first();

        self::assertSame(
            '{"rings":[[[18,-34],[18.1,-34],[18.1,-34.1],[18,-34]]]}',
            $http->lastParam('geometry'),
        );
        self::assertSame('esriGeometryPolygon', $http->lastParam('geometryType'));
    }

    public function testFieldsAndOrderAndDistinct(): void
    {
        $http = MockHttpClient::withBody('{"features": []}');
        $client = new Client('https://example.test', httpClient: $http);

        $client->layer(7)->query()
            ->fields('NAME', 'STAGE')
            ->orderBy('NAME')
            ->distinctValues()
            ->first();

        self::assertSame('NAME,STAGE', $http->lastParam('outFields'));
        self::assertSame('NAME', $http->lastParam('orderByFields'));
        self::assertSame('true', $http->lastParam('returnDistinctValues'));
    }

    public function testWithoutGeometry(): void
    {
        $http = MockHttpClient::withBody('{"features": []}');
        $client = new Client('https://example.test', httpClient: $http);

        $client->layer(7)->query()->withoutGeometry()->first();

        self::assertSame('false', $http->lastParam('returnGeometry'));
    }

    public function testFromPreservesLayerId(): void
    {
        $http = MockHttpClient::withBody('{"features": []}');
        $client = new Client('https://example.test', httpClient: $http);

        $base = new QueryParams(layerId: 111, where: 'STAGE = 4', fields: ['BLOCK_NAME', 'STAGE']);

        $builder = $client->layer(7)->query()->from($base);
        $params = $builder->params();

        self::assertSame(7, $params->layerId, 'layer ID from layer() must win over the merged params');
        self::assertSame('STAGE = 4', $params->where);
        self::assertSame(['BLOCK_NAME', 'STAGE'], $params->fields);
    }

    public function testParamsDoesNotExecute(): void
    {
        $http = MockHttpClient::withBody('{"features": []}');
        $client = new Client('https://example.test', httpClient: $http);

        $p = $client->layer(64)->query()
            ->where('YEAR = 2021')
            ->withoutGeometry()
            ->params();

        self::assertSame(64, $p->layerId);
        self::assertSame('YEAR = 2021', $p->where);
        self::assertSame(0, $http->calls, 'params() must not perform a request');
    }

    public function testQueryDoesNotMutateCallerParams(): void
    {
        $http = MockHttpClient::withBody('{"features": []}');
        $client = new Client('https://example.test', httpClient: $http);

        $p = new QueryParams(layerId: 7);
        $client->query($p);

        // Defaults are applied to an internal clone, not the caller's instance.
        self::assertSame('', $p->where);
        self::assertNull($p->format);
        self::assertSame(0, $p->pageSize);
    }
}

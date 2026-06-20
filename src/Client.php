<?php

declare(strict_types=1);

namespace RichardWooding\ArcGis;

use RichardWooding\ArcGis\Exception\ApiException;
use RichardWooding\ArcGis\Exception\TransportException;
use RichardWooding\ArcGis\Http\CurlHttpClient;
use RichardWooding\ArcGis\Http\HttpClient;
use RichardWooding\ArcGis\Http\Request;
use RichardWooding\ArcGis\Internal\Coerce;

/**
 * Client is the main entry point for interacting with an ArcGIS Feature
 * Service.
 *
 * It offers two interchangeable styles: a struct-based API ({@see QueryParams}
 * passed to {@see Client::query}) and a fluent builder
 * ({@see Client::layer} → {@see LayerClient::query}). Pagination, counts, and
 * object-ID-only queries are first-class.
 *
 *     $client = new Client($baseUrl);
 *     $features = $client->layer(7)->query()
 *         ->where('STAGE = 4')
 *         ->fields('NAME', 'STAGE')
 *         ->all();
 */
final class Client
{
    /**
     * maxQueryStringLen is the encoded-parameter length above which requests
     * are sent as POST instead of GET. A query carrying a detailed geometry
     * (e.g. a ward or suburb polygon with hundreds of vertices) easily exceeds
     * typical server URL-length limits (~2048), which manifests as an HTTP 404.
     * ArcGIS REST endpoints accept the same parameters by GET or POST, so we
     * fall back to POST.
     */
    private const MAX_QUERY_STRING_LEN = 1800;

    private readonly HttpClient $httpClient;

    /**
     * @param non-empty-string $baseUrl    the root of the FeatureServer, e.g.
     *                                      https://example.gov/arcgis/rest/services/Theme/Service/FeatureServer
     * @param HttpClient|null  $httpClient a custom transport; defaults to a curl
     *                                      transport using $timeout
     * @param string|null      $token      an ArcGIS token appended to every request
     * @param float            $timeout    request timeout in seconds for the default
     *                                      transport; ignored when $httpClient is supplied
     */
    public function __construct(
        private readonly string $baseUrl,
        ?HttpClient $httpClient = null,
        private readonly ?string $token = null,
        float $timeout = 30.0,
    ) {
        $this->httpClient = $httpClient ?? new CurlHttpClient($timeout);
    }

    /**
     * layer returns a LayerClient scoped to a specific layer ID.
     */
    public function layer(int $id): LayerClient
    {
        return new LayerClient($this, $id);
    }

    /**
     * serviceInfo fetches metadata about the feature service.
     */
    public function serviceInfo(): ServiceInfo
    {
        return ServiceInfo::fromArray($this->get($this->baseUrl, ['f' => 'json']));
    }

    /**
     * layerInfo fetches metadata for a specific layer.
     */
    public function layerInfo(int $layerId): LayerInfo
    {
        $endpoint = $this->baseUrl . '/' . $layerId;

        return LayerInfo::fromArray($this->get($endpoint, ['f' => 'json']));
    }

    /**
     * query executes a single-page query and returns the raw FeatureSet.
     */
    public function query(QueryParams $params): FeatureSet
    {
        $p = clone $params;
        $p->applyDefaults();

        return FeatureSet::fromArray($this->get($this->queryEndpoint($p), $p->toQueryParameters()));
    }

    /**
     * queryAll paginates through all results and returns every Feature.
     *
     * @return list<Feature>
     */
    public function queryAll(QueryParams $params): array
    {
        $p = clone $params;
        $p->applyDefaults();

        $all = [];
        while (true) {
            $fs = $this->query($p);
            foreach ($fs->features as $feature) {
                $all[] = $feature;
            }
            if (!$fs->exceededTransferLimit || $fs->features === []) {
                break;
            }
            $p->resultOffset += count($fs->features);
        }

        return $all;
    }

    /**
     * queryCount returns the count of features matching the query.
     */
    public function queryCount(QueryParams $params): int
    {
        $p = clone $params;
        $p->applyDefaults();
        $p->returnCountOnly = true;
        $p->format = OutputFormat::Json; // count responses are Esri JSON only

        $data = $this->get($this->queryEndpoint($p), $p->toQueryParameters());

        return Coerce::toInt($data['count'] ?? null);
    }

    /**
     * queryIds returns all object IDs matching the query.
     *
     * @return list<int>
     */
    public function queryIds(QueryParams $params): array
    {
        $p = clone $params;
        $p->applyDefaults();
        $p->returnIdsOnly = true;
        $p->format = OutputFormat::Json; // ID responses are Esri JSON only

        $data = $this->get($this->queryEndpoint($p), $p->toQueryParameters());

        $ids = [];
        foreach (Coerce::map($data['objectIds'] ?? null) as $id) {
            $ids[] = Coerce::toInt($id);
        }

        return $ids;
    }

    /**
     * @return non-empty-string
     */
    private function queryEndpoint(QueryParams $params): string
    {
        return $this->baseUrl . '/' . $params->layerId . '/query';
    }

    /**
     * get builds and sends a request, then decodes the JSON response.
     *
     * It centralises three concerns shared by every endpoint: the auth token is
     * appended here (so it applies to every request, not just queries); the
     * body is read once; and — critically — ArcGIS reports many failures with
     * HTTP 200 plus an {"error":{...}} envelope, which is detected and raised
     * as an {@see ApiException}.
     *
     * @param non-empty-string      $endpoint
     * @param array<string, string> $params
     *
     * @return array<string, mixed>
     *
     * @throws ApiException       when ArcGIS returns an error envelope
     * @throws TransportException on a non-200 status or undecodable body
     */
    private function get(string $endpoint, array $params): array
    {
        if ($this->token !== null && $this->token !== '') {
            $params['token'] = $this->token;
        }

        $response = $this->httpClient->send($this->buildRequest($endpoint, $params));

        if ($response->statusCode !== 200) {
            throw new TransportException(sprintf(
                'HTTP %d: %s',
                $response->statusCode,
                substr($response->body, 0, 4096),
            ));
        }

        try {
            $decoded = json_decode($response->body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new TransportException('decode response: ' . $e->getMessage(), 0, $e);
        }
        if (!is_array($decoded)) {
            throw new TransportException('decode response: expected a JSON object');
        }
        $data = Coerce::map($decoded);

        // ArcGIS often reports failures with HTTP 200 and an error envelope.
        if (isset($data['error']) && is_array($data['error'])) {
            throw ApiException::fromArray(Coerce::map($data['error']));
        }

        return $data;
    }

    /**
     * buildRequest builds a GET request, falling back to a POST with a
     * form-encoded body when the query string is large enough to risk exceeding
     * server URL-length limits (e.g. a query carrying a detailed polygon
     * geometry).
     *
     * @param non-empty-string      $endpoint
     * @param array<string, string> $params
     */
    private function buildRequest(string $endpoint, array $params): Request
    {
        $encoded = http_build_query($params);
        if (strlen($encoded) <= self::MAX_QUERY_STRING_LEN) {
            return new Request('GET', $endpoint . '?' . $encoded);
        }

        return new Request(
            'POST',
            $endpoint,
            $encoded,
            ['Content-Type' => 'application/x-www-form-urlencoded'],
        );
    }
}

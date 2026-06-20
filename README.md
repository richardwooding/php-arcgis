# php-arcgis

[![CI](https://github.com/richardwooding/php-arcgis/actions/workflows/ci.yml/badge.svg)](https://github.com/richardwooding/php-arcgis/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

A small, dependency-free PHP client for querying **ArcGIS Feature Services**
over their REST API. It handles pagination, counts, and object-ID-only queries,
and offers two interchangeable styles: a plain value-object API and a fluent
builder.

This is a PHP port of [`go-arcgis`](https://github.com/richardwooding/go-arcgis)
and tracks its design closely.

```php
use RichardWooding\ArcGis\Client;

$client = new Client($baseUrl);

$features = $client->layer(7)->query()
    ->where('STAGE = 4')
    ->fields('BLOCK_NAME', 'STAGE')
    ->all(); // paginates automatically
```

## Install

```sh
composer require richardwooding/arcgis
```

Requires PHP 8.2+ and the `curl` and `json` extensions. No third-party Composer
dependencies.

## Two styles, one engine

The fluent builder and the value-object API produce the same `QueryParams` and
hit the same code path — pick whichever reads better at the call site.

### Value-object style — explicit and serializable

```php
use RichardWooding\ArcGis\QueryParams;

$fs = $client->query(new QueryParams(
    layerId: 7,
    where: 'STAGE = 4',
    fields: ['BLOCK_NAME', 'STAGE'],
    pageSize: 100,
));
```

### Fluent style — readable and chainable

```php
$features = $client->layer(7)->query()
    ->where('STAGE = 4')
    ->fields('BLOCK_NAME', 'STAGE')
    ->withinEnvelope(18.4, -34.0, 18.6, -33.8)
    ->all();
```

## Querying

| Call | Returns | Notes |
| --- | --- | --- |
| `query` / `->first` | `FeatureSet` | a single page |
| `queryAll` / `->all` | `list<Feature>` | follows `exceededTransferLimit` until exhausted |
| `queryCount` / `->count` | `int` | no feature data transferred |
| `queryIds` / `->ids` | `list<int>` | object IDs only |

Pagination is automatic: `queryAll` keeps advancing `resultOffset` while the
service reports `exceededTransferLimit`.

Attributes are exposed uniformly regardless of the response format —
`Feature::attrs()` returns the Esri-JSON `attributes` map, falling back to the
GeoJSON `properties` map:

```php
foreach ($features as $f) {
    echo $f->attrs()['BLOCK_NAME'], "\n";
}
```

## Spatial filters

```php
// Bounding box (lon/lat)
$client->layer(7)->query()->withinEnvelope(18.4, -34.0, 18.6, -33.8);

// Point with an explicit relationship
use RichardWooding\ArcGis\SpatialRel;

$client->layer(7)->query()
    ->intersectsPoint(18.42, -33.92)
    ->spatialRel(SpatialRel::Within);

// Polygon (one or more [x, y] rings)
$client->layer(7)->query()->withinPolygon([
    [[18.0, -34.0], [18.1, -34.0], [18.1, -34.1], [18.0, -34.0]],
]);
```

Geometry filters default to the WGS84 (`inSR` 4326) spatial reference and the
`intersects` relationship. Override `inSR` when your coordinates are in another
spatial reference, or a WGS84 box will silently match nothing against a layer
stored in, say, Web Mercator.

## Output format

GeoJSON is the default. Switch to Esri JSON when you need its richer metadata
(`objectIdFieldName`, field definitions):

```php
use RichardWooding\ArcGis\OutputFormat;

$client->layer(7)->query()->format(OutputFormat::Json)->first();
```

`count` and `ids` always use Esri JSON internally, since that is the only format
ArcGIS returns those responses in.

## Options

```php
use RichardWooding\ArcGis\Client;

$client = new Client(
    $baseUrl,
    token: '…',      // authenticated services; appended to every request
    timeout: 10.0,   // seconds, for the default curl transport
);

// Or bring your own transport (e.g. a Guzzle/PSR-18 wrapper):
$client = new Client($baseUrl, httpClient: $myHttpClient);
```

A token, when set, is appended to every request — queries, counts, service and
layer metadata.

## Errors

ArcGIS frequently reports failures with an HTTP 200 status and an error envelope
in the body. `php-arcgis` surfaces these as `ApiException`:

```php
use RichardWooding\ArcGis\Exception\ApiException;

try {
    $client->query($params);
} catch (ApiException $e) {
    echo $e->getCode(), ': ', $e->getArcGisMessage(), "\n";
    print_r($e->getDetails());
}
```

Transport failures, non-200 statuses, and undecodable bodies are raised as
`TransportException`. Both extend `ArcGisException`.

## Large queries

A query carrying a detailed geometry (e.g. a ward or suburb polygon with
hundreds of vertices) can exceed typical server URL-length limits, which ArcGIS
manifests as an HTTP 404. The client transparently falls back from a GET to a
form-encoded POST when the encoded query string grows large; ArcGIS REST
endpoints accept the same parameters either way.

## Named services

Build named layer IDs and pre-built `QueryParams` for a specific service, then
extend them at the call site with `->from(...)`:

```php
function loadSheddingForStage(int $stage): QueryParams
{
    return new QueryParams(
        layerId: 111,
        where: "STAGE = {$stage}",
        fields: ['BLOCK_NAME', 'STAGE'],
    );
}

$features = $client->layer(111)->query()
    ->from(loadSheddingForStage(4))
    ->withinEnvelope(18.4, -34.0, 18.6, -33.8)
    ->all();
```

## Development

```sh
composer install
composer test    # PHPUnit (no live network — uses a mock transport)
composer stan    # PHPStan (max level)
composer lint    # PHP-CS-Fixer dry run
composer fix     # PHP-CS-Fixer apply
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

[MIT](LICENSE)

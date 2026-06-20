# Changelog

All notable changes to this project are documented here. The format is based on
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project
adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial PHP port of [`go-arcgis`](https://github.com/richardwooding/go-arcgis).
- `Client` constructed with a base URL and optional named arguments
  (`httpClient`, `token`, `timeout`). A token, when set, is applied to every
  request.
- Struct-style querying: `Client::query`, `queryAll` (automatic pagination via
  `exceededTransferLimit`), `queryCount`, and `queryIds`.
- Fluent `QueryBuilder` (`Client::layer($id)->query()`) with `where`, `fields`,
  `withinEnvelope`, `withinPolygon`, `intersectsPoint`, `inSR`, `spatialRel`,
  `orderBy`, `groupBy`, `distinctValues`, `offset`, `pageSize`,
  `withoutGeometry`, `format`, and `from`; terminal methods `first`, `all`,
  `count`, and `ids`.
- Service and layer metadata via `Client::serviceInfo` and `Client::layerInfo` /
  `LayerClient::info`.
- `Feature::attrs()` returning attributes regardless of GeoJSON vs Esri JSON
  format.
- `ApiException` surfacing ArcGIS error envelopes returned with an HTTP 200
  status.
- Dependency-free `CurlHttpClient` default transport plus an injectable
  `HttpClient` interface for bringing your own (e.g. a Guzzle/PSR-18 wrapper).
- Automatic GET → POST fallback for queries whose encoded parameters would risk
  exceeding server URL-length limits (e.g. a detailed polygon filter).

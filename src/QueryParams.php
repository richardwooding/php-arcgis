<?php

declare(strict_types=1);

namespace RichardWooding\ArcGis;

use RichardWooding\ArcGis\Geometry\Envelope;
use RichardWooding\ArcGis\Geometry\Point;
use RichardWooding\ArcGis\Geometry\Polygon;

/**
 * QueryParams defines all parameters for an ArcGIS feature query. It can be
 * used directly (struct-style) or built via {@see QueryBuilder} (fluent-style).
 *
 * The default value is usable: defaults are applied for an unset where clause
 * ("1=1"), format (GeoJSON), and page size (1000).
 *
 * Pass values by name to the constructor:
 *
 *     new QueryParams(layerId: 7, where: 'STAGE = 4', fields: ['NAME', 'STAGE']);
 */
final class QueryParams
{
    /**
     * @param list<string>     $fields
     * @param list<string>     $orderByFields
     * @param list<string>     $groupByFields
     * @param bool|null        $returnGeometry null = server default (true)
     */
    public function __construct(
        public int $layerId = 0,
        public string $where = '',
        public array $fields = [],
        public ?Envelope $envelope = null,
        public ?Point $geometry = null,
        public ?Polygon $polygon = null,
        public ?GeometryType $geometryType = null,
        public ?SpatialRel $spatialRel = null,
        /**
         * inSR is the well-known ID of the spatial reference the input geometry
         * (envelope/geometry/polygon) is expressed in. When zero and a geometry
         * filter is set, it defaults to 4326 (WGS84 longitude/latitude) — the
         * SR of the coordinates callers normally supply. Without it, ArcGIS
         * assumes the geometry is in the layer's native SR, so a WGS84 box
         * silently matches nothing against a layer stored in Web Mercator.
         */
        public int $inSR = 0,
        public array $orderByFields = [],
        public array $groupByFields = [],
        public int $resultOffset = 0,
        public int $pageSize = 0,
        public ?bool $returnGeometry = null,
        public bool $returnIdsOnly = false,
        public bool $returnCountOnly = false,
        /**
         * returnDistinctValues requests only distinct values for the selected
         * fields. Typically combined with fields (and often orderByFields) to
         * enumerate the values present in one or more columns.
         */
        public bool $returnDistinctValues = false,
        public ?OutputFormat $format = null,
    ) {
    }

    /**
     * hasGeometryFilter reports whether any spatial filter geometry is set.
     */
    public function hasGeometryFilter(): bool
    {
        return $this->envelope !== null || $this->geometry !== null || $this->polygon !== null;
    }

    /**
     * applyDefaults fills in sensible defaults for unset fields, mutating this
     * instance. Callers that must preserve the original should clone first.
     */
    public function applyDefaults(): void
    {
        if ($this->where === '') {
            $this->where = '1=1';
        }
        if ($this->format === null) {
            $this->format = OutputFormat::GeoJson;
        }
        if ($this->geometryType === null) {
            $this->geometryType = match (true) {
                $this->envelope !== null => GeometryType::Envelope,
                $this->geometry !== null => GeometryType::Point,
                $this->polygon !== null => GeometryType::Polygon,
                default => null,
            };
        }
        if ($this->spatialRel === null && $this->hasGeometryFilter()) {
            $this->spatialRel = SpatialRel::Intersects;
        }
        if ($this->inSR === 0 && $this->hasGeometryFilter()) {
            $this->inSR = 4326;
        }
        if ($this->pageSize === 0) {
            $this->pageSize = 1000;
        }
    }

    /**
     * toQueryParameters converts these params to the ArcGIS REST query
     * parameter map. Values are left unescaped; the client encodes them.
     *
     * @return array<string, string>
     */
    public function toQueryParameters(): array
    {
        $v = [];
        $v['f'] = ($this->format ?? OutputFormat::GeoJson)->value;
        $v['where'] = $this->where;
        $v['resultOffset'] = (string) $this->resultOffset;
        $v['resultRecordCount'] = (string) $this->pageSize;

        $v['outFields'] = $this->fields !== [] ? implode(',', $this->fields) : '*';

        $geometryType = $this->geometryType?->value;
        $spatialRel = $this->spatialRel?->value;
        if ($this->envelope !== null) {
            $v['geometry'] = $this->envelope->bbox();
        } elseif ($this->geometry !== null) {
            $v['geometry'] = $this->geometry->coords();
        } elseif ($this->polygon !== null) {
            $v['geometry'] = $this->polygon->esriJson();
        }
        if (isset($v['geometry'])) {
            if ($geometryType !== null) {
                $v['geometryType'] = $geometryType;
            }
            if ($spatialRel !== null) {
                $v['spatialRel'] = $spatialRel;
            }
        }
        if ($this->inSR !== 0 && $this->hasGeometryFilter()) {
            $v['inSR'] = (string) $this->inSR;
        }

        if ($this->orderByFields !== []) {
            $v['orderByFields'] = implode(',', $this->orderByFields);
        }
        if ($this->groupByFields !== []) {
            $v['groupByFieldsForStatistics'] = implode(',', $this->groupByFields);
        }

        if ($this->returnIdsOnly) {
            $v['returnIdsOnly'] = 'true';
        }
        if ($this->returnCountOnly) {
            $v['returnCountOnly'] = 'true';
        }
        if ($this->returnDistinctValues) {
            $v['returnDistinctValues'] = 'true';
        }
        if ($this->returnGeometry === false) {
            $v['returnGeometry'] = 'false';
        }

        return $v;
    }
}

<?php

declare(strict_types=1);

namespace RichardWooding\ArcGis;

use RichardWooding\ArcGis\Geometry\Envelope;
use RichardWooding\ArcGis\Geometry\Point;
use RichardWooding\ArcGis\Geometry\Polygon;

/**
 * QueryBuilder builds a {@see QueryParams} using a fluent, chainable API. Its
 * terminal methods (first/all/count/ids) delegate to the corresponding
 * {@see Client} method.
 */
final class QueryBuilder
{
    public function __construct(
        private readonly Client $client,
        private QueryParams $params,
    ) {
    }

    /**
     * where sets the SQL WHERE clause.
     */
    public function where(string $clause): self
    {
        $this->params->where = $clause;

        return $this;
    }

    /**
     * fields sets the output fields to return.
     */
    public function fields(string ...$fields): self
    {
        $this->params->fields = array_values($fields);

        return $this;
    }

    /**
     * withinEnvelope sets a bounding-box spatial filter.
     */
    public function withinEnvelope(float $minX, float $minY, float $maxX, float $maxY): self
    {
        $this->params->envelope = new Envelope($minX, $minY, $maxX, $maxY);

        return $this;
    }

    /**
     * withinPolygon sets a polygon spatial filter from one or more [x, y] rings.
     *
     * @param list<list<list<float>>> $rings
     */
    public function withinPolygon(array $rings): self
    {
        $this->params->polygon = new Polygon($rings);

        return $this;
    }

    /**
     * inSR sets the spatial reference (well-known ID) of the input filter
     * geometry.
     */
    public function inSR(int $wkid): self
    {
        $this->params->inSR = $wkid;

        return $this;
    }

    /**
     * intersectsPoint sets a point spatial filter using the default
     * "intersects" relationship.
     */
    public function intersectsPoint(float $x, float $y): self
    {
        $this->params->geometry = new Point($x, $y);

        return $this;
    }

    /**
     * spatialRel overrides the spatial relationship applied to the geometry
     * filter.
     */
    public function spatialRel(SpatialRel $rel): self
    {
        $this->params->spatialRel = $rel;

        return $this;
    }

    /**
     * orderBy sets the ORDER BY fields.
     */
    public function orderBy(string ...$fields): self
    {
        $this->params->orderByFields = array_values($fields);

        return $this;
    }

    /**
     * groupBy sets the GROUP BY fields (used with statistics queries).
     */
    public function groupBy(string ...$fields): self
    {
        $this->params->groupByFields = array_values($fields);

        return $this;
    }

    /**
     * distinctValues requests only distinct values for the selected fields.
     */
    public function distinctValues(): self
    {
        $this->params->returnDistinctValues = true;

        return $this;
    }

    /**
     * offset sets the starting record offset for pagination.
     */
    public function offset(int $n): self
    {
        $this->params->resultOffset = $n;

        return $this;
    }

    /**
     * pageSize sets the number of records per page.
     */
    public function pageSize(int $n): self
    {
        $this->params->pageSize = $n;

        return $this;
    }

    /**
     * withoutGeometry omits geometry from the response (faster for
     * attribute-only queries).
     */
    public function withoutGeometry(): self
    {
        $this->params->returnGeometry = false;

        return $this;
    }

    /**
     * format sets the response format.
     */
    public function format(OutputFormat $format): self
    {
        $this->params->format = $format;

        return $this;
    }

    /**
     * from merges a pre-built QueryParams into this builder (useful for named
     * queries). The layer ID set by Client::layer() is preserved.
     */
    public function from(QueryParams $base): self
    {
        $layerId = $this->params->layerId;
        $this->params = clone $base;
        $this->params->layerId = $layerId;

        return $this;
    }

    /**
     * params returns a copy of the underlying QueryParams for inspection or
     * reuse, without executing the query.
     */
    public function params(): QueryParams
    {
        return clone $this->params;
    }

    /**
     * first fetches only the first page of results.
     */
    public function first(): FeatureSet
    {
        return $this->client->query($this->params);
    }

    /**
     * all fetches all results, handling pagination automatically.
     *
     * @return list<Feature>
     */
    public function all(): array
    {
        return $this->client->queryAll($this->params);
    }

    /**
     * count returns only the record count matching the query.
     */
    public function count(): int
    {
        return $this->client->queryCount($this->params);
    }

    /**
     * ids returns only the object IDs matching the query.
     *
     * @return list<int>
     */
    public function ids(): array
    {
        return $this->client->queryIds($this->params);
    }
}

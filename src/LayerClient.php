<?php

declare(strict_types=1);

namespace RichardWooding\ArcGis;

/**
 * LayerClient is scoped to a single layer and provides the fluent query entry
 * point.
 */
final class LayerClient
{
    public function __construct(
        private readonly Client $client,
        private readonly int $layerId,
    ) {
    }

    /**
     * query returns a QueryBuilder pre-scoped to this layer.
     */
    public function query(): QueryBuilder
    {
        return new QueryBuilder($this->client, new QueryParams(layerId: $this->layerId));
    }

    /**
     * info fetches metadata for this layer.
     */
    public function info(): LayerInfo
    {
        return $this->client->layerInfo($this->layerId);
    }
}

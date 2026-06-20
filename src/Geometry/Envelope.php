<?php

declare(strict_types=1);

namespace RichardWooding\ArcGis\Geometry;

/**
 * Envelope is a bounding-box spatial filter, expressed in the layer's
 * coordinate system (typically WGS84 longitude/latitude).
 */
final class Envelope
{
    public function __construct(
        public readonly float $minX,
        public readonly float $minY,
        public readonly float $maxX,
        public readonly float $maxY,
    ) {
    }

    /**
     * bbox renders the envelope as the comma-delimited "minx,miny,maxx,maxy"
     * string ArcGIS accepts for an envelope geometry.
     */
    public function bbox(): string
    {
        return implode(',', [
            Coordinates::formatFloat($this->minX),
            Coordinates::formatFloat($this->minY),
            Coordinates::formatFloat($this->maxX),
            Coordinates::formatFloat($this->maxY),
        ]);
    }
}

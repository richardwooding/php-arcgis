<?php

declare(strict_types=1);

namespace RichardWooding\ArcGis\Geometry;

/**
 * Point is a single coordinate, expressed in the layer's coordinate system
 * (typically WGS84 longitude/latitude).
 */
final class Point
{
    public function __construct(
        public readonly float $x,
        public readonly float $y,
    ) {
    }

    /**
     * coords renders the point as the comma-delimited "x,y" string ArcGIS
     * accepts for a point geometry.
     */
    public function coords(): string
    {
        return Coordinates::formatFloat($this->x) . ',' . Coordinates::formatFloat($this->y);
    }
}

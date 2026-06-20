<?php

declare(strict_types=1);

namespace RichardWooding\ArcGis\Geometry;

/**
 * Polygon is a polygon spatial filter, expressed as one or more linear rings of
 * [x, y] coordinates in the layer's coordinate system (typically WGS84
 * longitude/latitude). Per the Esri convention an exterior ring is clockwise
 * and holes are counter-clockwise, but ArcGIS query filters tolerate either.
 */
final class Polygon
{
    /**
     * @param list<list<list<float>>> $rings one or more rings of [x, y] points
     */
    public function __construct(
        public readonly array $rings,
    ) {
    }

    /**
     * esriJson renders the polygon as the Esri JSON geometry string ArcGIS
     * accepts for a polygon filter: {"rings":[[[x,y],...],...]}.
     */
    public function esriJson(): string
    {
        $parts = [];
        foreach ($this->rings as $ring) {
            $points = [];
            foreach ($ring as $pt) {
                $x = $pt[0] ?? 0.0;
                $y = $pt[1] ?? 0.0;
                $points[] = '[' . Coordinates::formatFloat((float) $x) . ',' . Coordinates::formatFloat((float) $y) . ']';
            }
            $parts[] = '[' . implode(',', $points) . ']';
        }

        return '{"rings":[' . implode(',', $parts) . ']}';
    }
}

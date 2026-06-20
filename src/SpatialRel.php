<?php

declare(strict_types=1);

namespace RichardWooding\ArcGis;

/**
 * SpatialRel defines the spatial relationship applied to a geometry filter.
 */
enum SpatialRel: string
{
    case Intersects = 'esriSpatialRelIntersects';
    case Contains = 'esriSpatialRelContains';
    case Within = 'esriSpatialRelWithin';
    case Touches = 'esriSpatialRelTouches';
    case Overlaps = 'esriSpatialRelOverlaps';
    case EnvelopeIntersects = 'esriSpatialRelEnvelopeIntersects';
}

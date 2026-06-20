<?php

declare(strict_types=1);

namespace RichardWooding\ArcGis;

/**
 * GeometryType identifies the type of a spatial filter input geometry.
 */
enum GeometryType: string
{
    case Envelope = 'esriGeometryEnvelope';
    case Point = 'esriGeometryPoint';
    case Polygon = 'esriGeometryPolygon';
}

<?php

declare(strict_types=1);

namespace RichardWooding\ArcGis;

/**
 * OutputFormat controls the response format requested from ArcGIS.
 */
enum OutputFormat: string
{
    /**
     * GeoJson requests RFC 7946 GeoJSON. Each feature carries its attributes
     * under "properties".
     */
    case GeoJson = 'geojson';

    /**
     * Json requests Esri JSON. Each feature carries its attributes under
     * "attributes".
     */
    case Json = 'json';

    /**
     * Pbf requests the Protocol Buffer encoding. This package decodes JSON
     * responses only; use Pbf only with a custom decoder.
     */
    case Pbf = 'pbf';
}

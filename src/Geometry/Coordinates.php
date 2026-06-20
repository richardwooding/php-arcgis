<?php

declare(strict_types=1);

namespace RichardWooding\ArcGis\Geometry;

/**
 * Internal helper for rendering coordinates the way ArcGIS expects them on the
 * wire.
 *
 * @internal
 */
final class Coordinates
{
    /**
     * formatFloat renders a coordinate without scientific notation or trailing
     * zero padding, preserving full precision. It mirrors Go's
     * strconv.FormatFloat(f, 'f', -1, 64): integral values render without a
     * decimal point ("-34", not "-34.0").
     */
    public static function formatFloat(float $f): string
    {
        if (is_nan($f) || is_infinite($f)) {
            return '0';
        }
        if ($f === floor($f) && abs($f) < 1e15) {
            return (string) (int) $f;
        }
        $s = (string) $f;
        if (stripos($s, 'e') !== false) {
            // Expand scientific notation, then trim trailing zeros and dot.
            $s = rtrim(rtrim(sprintf('%.14F', $f), '0'), '.');
        }

        return $s;
    }
}

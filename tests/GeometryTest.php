<?php

declare(strict_types=1);

namespace RichardWooding\ArcGis\Tests;

use PHPUnit\Framework\TestCase;
use RichardWooding\ArcGis\Geometry\Envelope;
use RichardWooding\ArcGis\Geometry\Point;
use RichardWooding\ArcGis\Geometry\Polygon;

final class GeometryTest extends TestCase
{
    public function testEnvelopeBbox(): void
    {
        $e = new Envelope(18.4, -34.0, 18.6, -33.8);
        self::assertSame('18.4,-34,18.6,-33.8', $e->bbox());
    }

    public function testPointCoords(): void
    {
        $p = new Point(18.42, -33.92);
        self::assertSame('18.42,-33.92', $p->coords());
    }

    public function testIntegralFloatsHaveNoDecimalPoint(): void
    {
        // Matches Go's strconv.FormatFloat(f, 'f', -1, 64): "-34", not "-34.0".
        $e = new Envelope(18.0, -34.0, 19.0, -33.0);
        self::assertSame('18,-34,19,-33', $e->bbox());
    }

    public function testPolygonEsriJson(): void
    {
        $poly = new Polygon([
            [[18.0, -34.0], [18.1, -34.0], [18.1, -34.1], [18.0, -34.0]],
        ]);
        self::assertSame(
            '{"rings":[[[18,-34],[18.1,-34],[18.1,-34.1],[18,-34]]]}',
            $poly->esriJson(),
        );
    }

    public function testPolygonMultipleRings(): void
    {
        $poly = new Polygon([
            [[0.0, 0.0], [1.0, 0.0], [0.0, 1.0]],
            [[0.25, 0.25], [0.5, 0.25], [0.25, 0.5]],
        ]);
        self::assertSame(
            '{"rings":[[[0,0],[1,0],[0,1]],[[0.25,0.25],[0.5,0.25],[0.25,0.5]]]}',
            $poly->esriJson(),
        );
    }
}

<?php

declare(strict_types=1);

namespace RichardWooding\ArcGis;

use RichardWooding\ArcGis\Internal\Coerce;

/**
 * Feature represents a single ArcGIS feature with geometry and attributes.
 *
 * The geometry is left as the decoded JSON value because its shape depends on
 * the requested format (Esri JSON vs GeoJSON) and the layer's geometry type.
 * Attributes are populated for Esri JSON responses; properties for GeoJSON.
 */
final class Feature
{
    /**
     * @param mixed                     $geometry   the decoded geometry (array, or null)
     * @param array<string, mixed>|null $attributes Esri JSON attributes
     * @param array<string, mixed>|null $properties GeoJSON properties
     */
    public function __construct(
        public readonly mixed $geometry = null,
        public readonly ?array $attributes = null,
        public readonly ?array $properties = null,
    ) {
    }

    /**
     * attrs returns the feature's attribute map regardless of response format,
     * preferring Esri JSON attributes and falling back to GeoJSON properties.
     *
     * @return array<string, mixed>
     */
    public function attrs(): array
    {
        return $this->attributes ?? $this->properties ?? [];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['geometry'] ?? null,
            Coerce::mapOrNull($data['attributes'] ?? null),
            Coerce::mapOrNull($data['properties'] ?? null),
        );
    }
}

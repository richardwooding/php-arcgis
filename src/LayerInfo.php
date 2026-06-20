<?php

declare(strict_types=1);

namespace RichardWooding\ArcGis;

use RichardWooding\ArcGis\Internal\Coerce;

/**
 * LayerInfo contains metadata about a feature layer.
 */
final class LayerInfo
{
    /**
     * @param list<Field> $fields
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $type,
        public readonly string $description,
        public readonly int $maxRecordCount,
        public readonly array $fields,
        public readonly string $geometryType,
        public readonly bool $supportsStatistics,
        public readonly bool $supportsPagination,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $fields = [];
        foreach (Coerce::map($data['fields'] ?? null) as $field) {
            $fields[] = Field::fromArray(Coerce::map($field));
        }

        return new self(
            Coerce::toInt($data['id'] ?? null),
            Coerce::toString($data['name'] ?? null),
            Coerce::toString($data['type'] ?? null),
            Coerce::toString($data['description'] ?? null),
            Coerce::toInt($data['maxRecordCount'] ?? null),
            $fields,
            Coerce::toString($data['geometryType'] ?? null),
            Coerce::toBool($data['supportsStatistics'] ?? false),
            Coerce::toBool($data['supportsPagination'] ?? false),
        );
    }
}

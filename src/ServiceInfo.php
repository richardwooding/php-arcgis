<?php

declare(strict_types=1);

namespace RichardWooding\ArcGis;

use RichardWooding\ArcGis\Internal\Coerce;

/**
 * ServiceInfo contains metadata about a feature service.
 */
final class ServiceInfo
{
    /**
     * @param list<LayerRef> $layers
     * @param list<LayerRef> $tables
     */
    public function __construct(
        public readonly string $serviceDescription,
        public readonly array $layers,
        public readonly array $tables,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            Coerce::toString($data['serviceDescription'] ?? null),
            self::refs($data['layers'] ?? null),
            self::refs($data['tables'] ?? null),
        );
    }

    /**
     * @return list<LayerRef>
     */
    private static function refs(mixed $value): array
    {
        $refs = [];
        foreach (Coerce::map($value) as $ref) {
            $refs[] = LayerRef::fromArray(Coerce::map($ref));
        }

        return $refs;
    }
}

<?php

declare(strict_types=1);

namespace RichardWooding\ArcGis;

use RichardWooding\ArcGis\Internal\Coerce;

/**
 * LayerRef is a lightweight layer reference in a service listing.
 */
final class LayerRef
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            Coerce::toInt($data['id'] ?? null),
            Coerce::toString($data['name'] ?? null),
        );
    }
}

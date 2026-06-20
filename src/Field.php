<?php

declare(strict_types=1);

namespace RichardWooding\ArcGis;

use RichardWooding\ArcGis\Internal\Coerce;

/**
 * Field describes a single attribute field in a layer.
 */
final class Field
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $alias,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            Coerce::toString($data['name'] ?? null),
            Coerce::toString($data['type'] ?? null),
            Coerce::toString($data['alias'] ?? null),
        );
    }
}

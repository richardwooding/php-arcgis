<?php

declare(strict_types=1);

namespace RichardWooding\ArcGis;

use RichardWooding\ArcGis\Internal\Coerce;

/**
 * FeatureSet is a collection of features returned from a single-page query.
 */
final class FeatureSet
{
    /**
     * @param list<Feature> $features
     * @param list<Field>   $fields
     */
    public function __construct(
        public readonly array $features = [],
        public readonly bool $exceededTransferLimit = false,
        public readonly string $objectIdFieldName = '',
        public readonly array $fields = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $features = [];
        foreach (Coerce::map($data['features'] ?? null) as $feature) {
            $features[] = Feature::fromArray(Coerce::map($feature));
        }

        $fields = [];
        foreach (Coerce::map($data['fields'] ?? null) as $field) {
            $fields[] = Field::fromArray(Coerce::map($field));
        }

        return new self(
            $features,
            Coerce::toBool($data['exceededTransferLimit'] ?? false),
            Coerce::toString($data['objectIdFieldName'] ?? null),
            $fields,
        );
    }
}

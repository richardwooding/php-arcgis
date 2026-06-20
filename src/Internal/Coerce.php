<?php

declare(strict_types=1);

namespace RichardWooding\ArcGis\Internal;

/**
 * Coerce safely narrows the {@see mixed} values produced by json_decode into
 * the scalar and array shapes the response DTOs expect. ArcGIS responses are
 * generally well-formed, but decoding untyped JSON means every field is
 * {@see mixed} until proven otherwise; these helpers make that explicit instead
 * of casting blindly.
 *
 * @internal
 */
final class Coerce
{
    public static function toString(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    public static function toInt(mixed $value): int
    {
        return is_scalar($value) ? (int) $value : 0;
    }

    public static function toBool(mixed $value): bool
    {
        return (bool) $value;
    }

    /**
     * map coerces a value into a string-keyed array, returning an empty array
     * for non-array input.
     *
     * @return array<string, mixed>
     */
    public static function map(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $key => $item) {
            $out[(string) $key] = $item;
        }

        return $out;
    }

    /**
     * mapOrNull behaves like {@see map} but preserves the distinction between
     * an absent value (null) and a present-but-empty object.
     *
     * @return array<string, mixed>|null
     */
    public static function mapOrNull(mixed $value): ?array
    {
        return is_array($value) ? self::map($value) : null;
    }
}

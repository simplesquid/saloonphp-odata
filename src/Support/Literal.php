<?php

declare(strict_types=1);

namespace SimpleSquid\SaloonOData\Support;

use BackedEnum;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use SimpleSquid\SaloonOData\Enums\ODataVersion;
use SimpleSquid\SaloonOData\Exceptions\InvalidODataQueryException;
use Stringable;
use UnitEnum;

/**
 * Single source of truth for OData literal formatting.
 *
 * @see https://docs.oasis-open.org/odata/odata/v4.01/os/abnf/odata-abnf-construction-rules.txt
 */
final class Literal
{
    private const GUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    /**
     * Wrap a value to indicate date-only literal rendering.
     */
    public static function dateOnly(DateTimeInterface $value): DateOnly
    {
        return new DateOnly($value);
    }

    /**
     * Encode any supported PHP value as an OData literal for the given version.
     *
     * Supported types: scalars, null, arrays of supported values, DateTimeInterface,
     * DateOnly wrappers, BackedEnum and UnitEnum, and Stringable. Anything else
     * throws.
     *
     * @throws InvalidODataQueryException
     */
    public static function encode(mixed $value, ODataVersion $version): string
    {
        return match (true) {
            $value === null => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_int($value) => (string) $value,
            is_float($value) => self::encodeFloat($value),
            $value instanceof DateOnly => self::encodeDateOnly($value->value, $version),
            $value instanceof DateTimeInterface => self::encodeDateTime($value, $version),
            $value instanceof BackedEnum => self::encode($value->value, $version),
            $value instanceof UnitEnum => self::encodeString($value->name),
            is_array($value) => self::encodeCollection($value, $version),
            is_string($value) => self::encodeString($value, $version),
            $value instanceof Stringable => self::encodeString((string) $value, $version),
            default => throw new InvalidODataQueryException(sprintf(
                'Cannot encode value of type "%s" as an OData literal.',
                get_debug_type($value),
            )),
        };
    }

    /**
     * Encode an array as an OData collection literal: `(item1,item2,...)`.
     *
     * @param  array<int|string, mixed>  $values
     */
    public static function encodeCollection(array $values, ODataVersion $version): string
    {
        $items = array_map(static fn (mixed $item): string => self::encode($item, $version), array_values($values));

        return '('.implode(',', $items).')';
    }

    private static function encodeFloat(float $value): string
    {
        if (is_nan($value) || is_infinite($value)) {
            throw new InvalidODataQueryException('NaN and infinite floats cannot be encoded as OData literals.');
        }

        // Avoid locale-dependent rendering and trailing-zero noise.
        return rtrim(rtrim(sprintf('%.14F', $value), '0'), '.');
    }

    private static function encodeString(string $value, ODataVersion $version = ODataVersion::V4): string
    {
        if ($version === ODataVersion::V3 && self::looksLikeGuid($value)) {
            return "guid'{$value}'";
        }

        if ($version === ODataVersion::V4 && self::looksLikeGuid($value)) {
            return $value;
        }

        return "'".str_replace("'", "''", $value)."'";
    }

    private static function encodeDateTime(DateTimeInterface $value, ODataVersion $version): string
    {
        $utc = self::toUtc($value);

        return $version === ODataVersion::V3
            ? "datetime'".$utc->format('Y-m-d\TH:i:s')."'"
            : $utc->format('Y-m-d\TH:i:s\Z');
    }

    private static function encodeDateOnly(DateTimeInterface $value, ODataVersion $version): string
    {
        $date = $value->format('Y-m-d');

        return $version === ODataVersion::V3
            ? "datetime'{$date}'"
            : $date;
    }

    private static function toUtc(DateTimeInterface $value): DateTimeImmutable
    {
        $immutable = $value instanceof DateTimeImmutable
            ? $value
            : DateTimeImmutable::createFromInterface($value);

        return $immutable->setTimezone(new DateTimeZone('UTC'));
    }

    private static function looksLikeGuid(string $value): bool
    {
        return (bool) preg_match(self::GUID_PATTERN, $value);
    }
}

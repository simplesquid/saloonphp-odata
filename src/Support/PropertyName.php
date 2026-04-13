<?php

declare(strict_types=1);

namespace SimpleSquid\SaloonOData\Support;

use SimpleSquid\SaloonOData\Exceptions\InvalidODataQueryException;

/**
 * Validates property names before they are interpolated into OData expressions.
 *
 * Property names enter unescaped into $select, $orderby, $expand, and $filter
 * (left-hand side and inside string functions). If user input ever reaches
 * these positions the result is a filter-injection vector. Validation accepts
 * OData identifier syntax: a leading letter or underscore, followed by
 * letters/digits/underscores, with optional `.`/`/` separators for namespaces
 * and navigation paths.
 */
final class PropertyName
{
    private const PATTERN = '~^[A-Za-z_][A-Za-z0-9_]*([./][A-Za-z_][A-Za-z0-9_]*)*$~';

    public static function assert(string $property): void
    {
        if ($property === '' || preg_match(self::PATTERN, $property) !== 1) {
            throw new InvalidODataQueryException(sprintf(
                'Invalid OData property name "%s". Expected an identifier (letters, digits, underscores) optionally separated by `.` or `/`.',
                $property,
            ));
        }
    }
}

<?php

declare(strict_types=1);

namespace SimpleSquid\SaloonOData\Support;

use SimpleSquid\SaloonOData\Exceptions\InvalidODataQueryException;

/**
 * Marker wrapper signalling that a string should be encoded as an OData GUID
 * literal (bare in v4, `guid'...'` in v3) rather than as a quoted string.
 *
 * Constructed via {@see Literal::guid()}; validates the GUID format eagerly.
 */
final readonly class Guid
{
    private const GUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    public function __construct(public string $value)
    {
        if (preg_match(self::GUID_PATTERN, $value) !== 1) {
            throw new InvalidODataQueryException(sprintf(
                'Value "%s" is not a valid GUID.',
                $value,
            ));
        }
    }
}

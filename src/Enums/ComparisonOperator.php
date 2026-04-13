<?php

declare(strict_types=1);

namespace SimpleSquid\SaloonOData\Enums;

use SimpleSquid\SaloonOData\Exceptions\InvalidODataQueryException;
use SimpleSquid\SaloonOData\Exceptions\UnsupportedInVersionException;

enum ComparisonOperator: string
{
    case Eq = 'eq';
    case Ne = 'ne';
    case Gt = 'gt';
    case Ge = 'ge';
    case Lt = 'lt';
    case Le = 'le';
    case Has = 'has';
    case In = 'in';

    /**
     * Coerce a string or enum into a {@see ComparisonOperator} instance.
     *
     * @throws InvalidODataQueryException When the string does not match a known operator.
     */
    public static function coerce(self|string $operator): self
    {
        if ($operator instanceof self) {
            return $operator;
        }

        return self::tryFrom(strtolower($operator))
            ?? throw new InvalidODataQueryException(sprintf(
                'Unknown OData comparison operator "%s". Expected one of: %s.',
                $operator,
                implode(', ', array_column(self::cases(), 'value')),
            ));
    }

    /**
     * Throw if the operator is not available in the given OData version.
     *
     * @throws UnsupportedInVersionException
     */
    public function assertSupported(ODataVersion $version): void
    {
        if ($version === ODataVersion::V3 && in_array($this, [self::Has, self::In], true)) {
            throw new UnsupportedInVersionException(sprintf(
                'The "%s" operator is not available in OData v3.',
                $this->value,
            ));
        }
    }
}

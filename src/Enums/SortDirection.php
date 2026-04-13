<?php

declare(strict_types=1);

namespace SimpleSquid\SaloonOData\Enums;

use SimpleSquid\SaloonOData\Exceptions\InvalidODataQueryException;

enum SortDirection: string
{
    case Asc = 'asc';
    case Desc = 'desc';

    /**
     * @throws InvalidODataQueryException
     */
    public static function coerce(self|string $direction): self
    {
        if ($direction instanceof self) {
            return $direction;
        }

        return self::tryFrom(strtolower($direction))
            ?? throw new InvalidODataQueryException(sprintf(
                'Invalid sort direction "%s". Expected "asc" or "desc".',
                $direction,
            ));
    }
}

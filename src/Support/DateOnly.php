<?php

declare(strict_types=1);

namespace SimpleSquid\SaloonOData\Support;

use DateTimeInterface;

/**
 * Wraps a DateTimeInterface to signal date-only literal rendering
 * (e.g. `datetime'2025-01-01'` in v3, `2025-01-01` in v4).
 */
final readonly class DateOnly
{
    public function __construct(public DateTimeInterface $value) {}
}

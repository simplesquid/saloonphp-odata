<?php

declare(strict_types=1);

use SimpleSquid\SaloonOData\Enums\ODataVersion;
use SimpleSquid\SaloonOData\Support\Literal;

it('renders a date-only literal with no time component', function (): void {
    $date = new DateTimeImmutable('2025-01-15T10:30:00Z');

    expect(Literal::encode(Literal::dateOnly($date), ODataVersion::V4))->toBe('2025-01-15')
        ->and(Literal::encode(Literal::dateOnly($date), ODataVersion::V3))->toBe("datetime'2025-01-15'");
});

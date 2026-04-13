<?php

declare(strict_types=1);

use SimpleSquid\SaloonOData\Enums\ODataVersion;
use SimpleSquid\SaloonOData\Support\Literal;

it('wraps datetimes in datetime\'...\' for v3', function (): void {
    $dt = new DateTimeImmutable('2025-01-15T10:30:00+00:00');

    expect(Literal::encode($dt, ODataVersion::V3))->toBe("datetime'2025-01-15T10:30:00'");
});

it('wraps GUIDs in guid\'...\' for v3', function (): void {
    expect(Literal::encode('11111111-2222-3333-4444-555555555555', ODataVersion::V3))
        ->toBe("guid'11111111-2222-3333-4444-555555555555'");
});

it('uses the same string-quoting rules as v4 for plain strings', function (): void {
    expect(Literal::encode("O'Neill", ODataVersion::V3))->toBe("'O''Neill'");
});

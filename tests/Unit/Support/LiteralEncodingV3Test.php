<?php

declare(strict_types=1);

use SimpleSquid\SaloonOData\Enums\ODataVersion;
use SimpleSquid\SaloonOData\Exceptions\InvalidODataQueryException;
use SimpleSquid\SaloonOData\Support\Literal;

it('wraps datetimes in datetime\'...\' for v3', function (): void {
    $dt = new DateTimeImmutable('2025-01-15T10:30:00+00:00');

    expect(Literal::encode($dt, ODataVersion::V3))->toBe("datetime'2025-01-15T10:30:00'");
});

it('wraps GUIDs in guid\'...\' for v3 when wrapped via Literal::guid()', function (): void {
    expect(Literal::encode(Literal::guid('11111111-2222-3333-4444-555555555555'), ODataVersion::V3))
        ->toBe("guid'11111111-2222-3333-4444-555555555555'");
});

it('rejects a malformed GUID at construction time', function (): void {
    Literal::guid('not-a-guid');
})->throws(InvalidODataQueryException::class);

it('uses the same string-quoting rules as v4 for plain strings', function (): void {
    expect(Literal::encode("O'Neill", ODataVersion::V3))->toBe("'O''Neill'");
});

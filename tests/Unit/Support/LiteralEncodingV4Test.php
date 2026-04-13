<?php

declare(strict_types=1);

use SimpleSquid\SaloonOData\Enums\ODataVersion;
use SimpleSquid\SaloonOData\Support\Literal;

it('encodes scalars and null', function (mixed $value, string $expected): void {
    expect(Literal::encode($value, ODataVersion::V4))->toBe($expected);
})->with([
    'null' => [null, 'null'],
    'true' => [true, 'true'],
    'false' => [false, 'false'],
    'int' => [42, '42'],
    'negative' => [-7, '-7'],
    'float' => [3.14, '3.14'],
    'simple string' => ['foo', "'foo'"],
    'string with quote' => ["O'Neill", "'O''Neill'"],
    'empty string' => ['', "''"],
]);

it('encodes a DateTime as bare ISO-8601 with Z suffix in v4', function (): void {
    $dt = new DateTimeImmutable('2025-01-15T10:30:00+00:00');

    expect(Literal::encode($dt, ODataVersion::V4))->toBe('2025-01-15T10:30:00Z');
});

it('converts non-UTC DateTimes to UTC', function (): void {
    $dt = new DateTimeImmutable('2025-01-15T12:30:00+02:00');

    expect(Literal::encode($dt, ODataVersion::V4))->toBe('2025-01-15T10:30:00Z');
});

it('emits a bare GUID literal in v4 when a string matches the GUID pattern', function (): void {
    expect(Literal::encode('11111111-2222-3333-4444-555555555555', ODataVersion::V4))
        ->toBe('11111111-2222-3333-4444-555555555555');
});

it('encodes an array as a tuple via encodeCollection', function (): void {
    expect(Literal::encodeCollection(['A', 'B', 1], ODataVersion::V4))
        ->toBe("('A','B',1)");
});

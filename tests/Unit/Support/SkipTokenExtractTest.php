<?php

declare(strict_types=1);

use SimpleSquid\SaloonOData\Support\SkipToken;

it('extracts the skiptoken from a full URL', function (): void {
    $url = 'https://api.example.com/People?$top=60&$skiptoken=abc-123';

    expect(SkipToken::extract($url))->toBe('abc-123');
});

it('extracts the skiptoken from a relative URL', function (): void {
    expect(SkipToken::extract('/People?$skiptoken=cursor'))
        ->toBe('cursor');
});

it('extracts the skiptoken from a bare query string', function (): void {
    expect(SkipToken::extract('$top=60&$skiptoken=xyz'))->toBe('xyz')
        ->and(SkipToken::extract('?$skiptoken=xyz'))->toBe('xyz');
});

it('returns null when no skiptoken is present', function (): void {
    expect(SkipToken::extract('https://api.example.com/People?$top=60'))->toBeNull()
        ->and(SkipToken::extract(''))->toBeNull();
});

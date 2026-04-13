<?php

declare(strict_types=1);

use SimpleSquid\SaloonOData\Enums\ODataVersion;
use SimpleSquid\SaloonOData\Exceptions\UnsupportedInVersionException;
use SimpleSquid\SaloonOData\ODataQueryBuilder;

it('renders $count=true on v4', function (): void {
    expect(ODataQueryBuilder::make()->count()->toArray())
        ->toBe(['$count' => 'true']);
});

it('renders $count=false on v4 when explicitly disabled', function (): void {
    expect(ODataQueryBuilder::make()->count(false)->toArray())
        ->toBe(['$count' => 'false']);
});

it('renders $inlinecount=allpages on v3', function (): void {
    expect(ODataQueryBuilder::make(ODataVersion::V3)->count()->toArray())
        ->toBe(['$inlinecount' => 'allpages']);
});

it('renders $search on v4', function (): void {
    expect(ODataQueryBuilder::make()->search('foo bar')->toArray())
        ->toBe(['$search' => 'foo bar']);
});

it('throws when search is used on v3 (deferred to render)', function (): void {
    ODataQueryBuilder::make(ODataVersion::V3)->search('foo')->toArray();
})->throws(UnsupportedInVersionException::class);

it('renders $format', function (): void {
    expect(ODataQueryBuilder::make()->format('json')->toArray())
        ->toBe(['$format' => 'json']);
});

<?php

declare(strict_types=1);

use SimpleSquid\SaloonOData\Enums\ODataVersion;
use SimpleSquid\SaloonOData\Filter\FilterBuilder;
use SimpleSquid\SaloonOData\ODataQueryBuilder;

it('renders contains() in v4 form', function (): void {
    $params = ODataQueryBuilder::make()
        ->filter(fn (FilterBuilder $f) => $f->contains('Name', 'foo'))
        ->toArray();

    expect($params['$filter'])->toBe("contains(Name,'foo')");
});

it('renders contains() as substringof() in v3 with flipped args', function (): void {
    $params = ODataQueryBuilder::make(ODataVersion::V3)
        ->filter(fn (FilterBuilder $f) => $f->contains('Name', 'foo'))
        ->toArray();

    expect($params['$filter'])->toBe("substringof('foo',Name)");
});

it('renders startswith and endswith identically across versions', function (): void {
    foreach ([ODataVersion::V3, ODataVersion::V4] as $version) {
        $params = ODataQueryBuilder::make($version)
            ->filter(fn (FilterBuilder $f) => $f
                ->startsWith('Name', 'A')
                ->and()
                ->endsWith('Name', 'Z'))
            ->toArray();

        expect($params['$filter'])->toBe("startswith(Name,'A') and endswith(Name,'Z')");
    }
});

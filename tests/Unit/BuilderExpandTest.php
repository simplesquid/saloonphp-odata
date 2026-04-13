<?php

declare(strict_types=1);

use SimpleSquid\SaloonOData\Enums\ODataVersion;
use SimpleSquid\SaloonOData\Exceptions\UnsupportedInVersionException;
use SimpleSquid\SaloonOData\Expand\ExpandBuilder;
use SimpleSquid\SaloonOData\Filter\FilterBuilder;
use SimpleSquid\SaloonOData\ODataQueryBuilder;

it('renders flat $expand for bare navigation properties', function (): void {
    $params = ODataQueryBuilder::make()
        ->expand('Trips')
        ->expand('Friends')
        ->toArray();

    expect($params['$expand'])->toBe('Trips,Friends');
});

it('renders v4 nested options inside parentheses', function (): void {
    $params = ODataQueryBuilder::make()
        ->expand('Trips', fn (ExpandBuilder $e) => $e
            ->select('Name', 'Budget')
            ->filter(fn (FilterBuilder $f) => $f->where('Status', 'eq', 'Completed'))
            ->orderBy('Name')
            ->top(5)
            ->skip(10)
            ->count())
        ->toArray();

    expect($params['$expand'])->toBe(
        "Trips(\$select=Name,Budget;\$filter=Status eq 'Completed';\$orderby=Name asc;\$top=5;\$skip=10;\$count=true)",
    );
});

it('allows flat v3 expand using path syntax', function (): void {
    $params = ODataQueryBuilder::make(ODataVersion::V3)
        ->expand('Trips')
        ->expand('Trips/Stops')
        ->toArray();

    expect($params['$expand'])->toBe('Trips,Trips/Stops');
});

it('throws when a closure is passed to expand on a v3 builder', function (): void {
    ODataQueryBuilder::make(ODataVersion::V3)
        ->expand('Trips', fn (ExpandBuilder $e) => $e->select('Name'))
        ->toArray();
})->throws(UnsupportedInVersionException::class);

<?php

declare(strict_types=1);

use SimpleSquid\SaloonOData\Exceptions\InvalidODataQueryException;
use SimpleSquid\SaloonOData\Filter\FilterBuilder;
use SimpleSquid\SaloonOData\ODataQueryBuilder;

it('rejects property names that could inject filter syntax', function (string $name): void {
    ODataQueryBuilder::make()->select($name);
})->with([
    'space' => ['Name eq 1'],
    'apostrophe' => ["Name'or'1"],
    'paren' => ['Name)'],
    'leading digit' => ['1Name'],
    'leading dot' => ['.Name'],
    'empty' => [''],
])->throws(InvalidODataQueryException::class);

it('accepts valid property names', function (string $name): void {
    expect(ODataQueryBuilder::make()->select($name)->toArray())
        ->toBe(['$select' => $name]);
})->with([
    'simple' => ['Name'],
    'underscore' => ['_Name'],
    'digits' => ['Name123'],
    'navigation path' => ['Trips/Stops'],
    'namespace' => ['Edm.String'],
]);

it('also validates property names inside filter clauses', function (): void {
    ODataQueryBuilder::make()
        ->filter(fn (FilterBuilder $f) => $f->where("Name'; drop", 'eq', 'x'))
        ->toArray();
})->throws(InvalidODataQueryException::class);

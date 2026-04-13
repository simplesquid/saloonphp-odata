<?php

declare(strict_types=1);

use SimpleSquid\SaloonOData\Enums\SortDirection;
use SimpleSquid\SaloonOData\Exceptions\InvalidODataQueryException;
use SimpleSquid\SaloonOData\ODataQueryBuilder;

it('orders ascending by default', function (): void {
    expect(ODataQueryBuilder::make()->orderBy('Name')->toArray())
        ->toBe(['$orderby' => 'Name asc']);
});

it('accepts both string and enum directions', function (): void {
    $a = ODataQueryBuilder::make()->orderBy('Name', 'desc')->toArray();
    $b = ODataQueryBuilder::make()->orderBy('Name', SortDirection::Desc)->toArray();

    expect($a)->toEqual($b)->and($a['$orderby'])->toBe('Name desc');
});

it('joins multiple order clauses with commas', function (): void {
    $params = ODataQueryBuilder::make()
        ->orderBy('LastName')
        ->orderByDesc('CreatedAt')
        ->toArray();

    expect($params['$orderby'])->toBe('LastName asc,CreatedAt desc');
});

it('rejects a nonsense direction', function (): void {
    ODataQueryBuilder::make()->orderBy('Name', 'sideways');
})->throws(InvalidODataQueryException::class);

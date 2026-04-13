<?php

declare(strict_types=1);

use SimpleSquid\SaloonOData\Filter\FilterBuilder;
use SimpleSquid\SaloonOData\ODataQueryBuilder;

it('clearSelect wipes accumulated $select', function (): void {
    $params = ODataQueryBuilder::make()
        ->select('A', 'B')
        ->clearSelect()
        ->select('C')
        ->toArray();

    expect($params['$select'])->toBe('C');
});

it('replaceSelect swaps in a new list in one call', function (): void {
    $params = ODataQueryBuilder::make()
        ->select('A', 'B')
        ->replaceSelect('C', 'D')
        ->toArray();

    expect($params['$select'])->toBe('C,D');
});

it('clearOrderBy wipes ordering', function (): void {
    $params = ODataQueryBuilder::make()
        ->orderBy('A')
        ->clearOrderBy()
        ->orderByDesc('B')
        ->toArray();

    expect($params['$orderby'])->toBe('B desc');
});

it('clearExpand wipes expansions', function (): void {
    $params = ODataQueryBuilder::make()
        ->expand('A')
        ->expand('B')
        ->clearExpand()
        ->expand('C')
        ->toArray();

    expect($params['$expand'])->toBe('C');
});

it('replaceFilter discards prior fragments and replaces them', function (): void {
    $params = ODataQueryBuilder::make()
        ->filter(fn (FilterBuilder $f) => $f->whereEquals('A', 1))
        ->replaceFilter(fn (FilterBuilder $f) => $f->whereEquals('B', 2))
        ->toArray();

    expect($params['$filter'])->toBe('B eq 2');
});

it('replaceOrderBy discards prior clauses and replaces with a single one', function (): void {
    $params = ODataQueryBuilder::make()
        ->orderBy('A')
        ->orderByDesc('B')
        ->replaceOrderBy('C')
        ->toArray();

    expect($params['$orderby'])->toBe('C asc');
});

it('replaceExpand discards prior expansions', function (): void {
    $params = ODataQueryBuilder::make()
        ->expand('A')
        ->expand('B')
        ->replaceExpand('C')
        ->toArray();

    expect($params['$expand'])->toBe('C');
});

it('clearFilter wipes filter fragments', function (): void {
    $params = ODataQueryBuilder::make()
        ->filter(fn (FilterBuilder $f) => $f->whereEquals('A', 1))
        ->clearFilter()
        ->filter(fn (FilterBuilder $f) => $f->whereEquals('B', 2))
        ->toArray();

    expect($params['$filter'])->toBe('B eq 2');
});

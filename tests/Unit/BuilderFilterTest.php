<?php

declare(strict_types=1);

use SimpleSquid\SaloonOData\Enums\ComparisonOperator;
use SimpleSquid\SaloonOData\Exceptions\InvalidODataQueryException;
use SimpleSquid\SaloonOData\Filter\FilterBuilder;
use SimpleSquid\SaloonOData\ODataQueryBuilder;

it('renders a simple where clause', function (): void {
    $params = ODataQueryBuilder::make()
        ->filter(fn (FilterBuilder $f) => $f->where('Age', 'gt', 30))
        ->toArray();

    expect($params['$filter'])->toBe('Age gt 30');
});

it('accepts the comparison operator as either an enum or a string', function (): void {
    $a = ODataQueryBuilder::make()
        ->filter(fn (FilterBuilder $f) => $f->where('Age', ComparisonOperator::Gt, 30))
        ->toArray();

    $b = ODataQueryBuilder::make()
        ->filter(fn (FilterBuilder $f) => $f->where('Age', 'GT', 30))
        ->toArray();

    expect($a)->toEqual($b);
});

it('rejects an unknown string operator', function (): void {
    ODataQueryBuilder::make()->filter(
        fn (FilterBuilder $f) => $f->where('Age', 'between', 30),
    );
})->throws(InvalidODataQueryException::class);

it('joins consecutive clauses with `and` by default', function (): void {
    $params = ODataQueryBuilder::make()
        ->filter(fn (FilterBuilder $f) => $f
            ->where('Age', 'gt', 30)
            ->where('Status', 'eq', 'Active'))
        ->toArray();

    expect($params['$filter'])->toBe("Age gt 30 and Status eq 'Active'");
});

it('honours an explicit or() between clauses', function (): void {
    $params = ODataQueryBuilder::make()
        ->filter(fn (FilterBuilder $f) => $f
            ->where('Age', 'gt', 30)
            ->or()
            ->where('Status', 'eq', 'Active'))
        ->toArray();

    expect($params['$filter'])->toBe("Age gt 30 or Status eq 'Active'");
});

it('groups nested expressions in parentheses', function (): void {
    $params = ODataQueryBuilder::make()
        ->filter(fn (FilterBuilder $f) => $f
            ->where('Age', 'gt', 30)
            ->and()
            ->group(fn (FilterBuilder $g) => $g
                ->startsWith('Name', 'A')
                ->or()
                ->startsWith('Name', 'B')))
        ->toArray();

    expect($params['$filter'])->toBe("Age gt 30 and (startswith(Name,'A') or startswith(Name,'B'))");
});

it('can negate the next clause', function (): void {
    $params = ODataQueryBuilder::make()
        ->filter(fn (FilterBuilder $f) => $f
            ->not()
            ->where('Status', 'eq', 'Active'))
        ->toArray();

    expect($params['$filter'])->toBe("not Status eq 'Active'");
});

it('encodes the in() operator as a tuple', function (): void {
    $params = ODataQueryBuilder::make()
        ->filter(fn (FilterBuilder $f) => $f->in('Status', ['Active', 'Pending']))
        ->toArray();

    expect($params['$filter'])->toBe("Status in ('Active','Pending')");
});

it('escapes embedded apostrophes in string literals', function (): void {
    $params = ODataQueryBuilder::make()
        ->filter(fn (FilterBuilder $f) => $f->where('Name', 'eq', "O'Neill"))
        ->toArray();

    expect($params['$filter'])->toBe("Name eq 'O''Neill'");
});

it('refuses to start with a logical joiner', function (): void {
    ODataQueryBuilder::make()->filter(
        fn (FilterBuilder $f) => $f->and()->where('Age', 'gt', 30),
    );
})->throws(InvalidODataQueryException::class);

it('AND-merges multiple ->filter() calls', function (): void {
    $params = ODataQueryBuilder::make()
        ->filter(fn (FilterBuilder $f) => $f->where('A', 'eq', 1))
        ->filter(fn (FilterBuilder $f) => $f->where('B', 'eq', 2))
        ->toArray();

    expect($params['$filter'])->toBe('(A eq 1) and (B eq 2)');
});

it('supports filterRaw() as an escape hatch', function (): void {
    $params = ODataQueryBuilder::make()
        ->filterRaw('year(Created) eq 2025')
        ->toArray();

    expect($params['$filter'])->toBe('year(Created) eq 2025');
});

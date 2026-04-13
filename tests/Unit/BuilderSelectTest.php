<?php

declare(strict_types=1);

use SimpleSquid\SaloonOData\ODataQueryBuilder;

it('renders no select when empty', function (): void {
    expect(ODataQueryBuilder::make()->toArray())->toBe([]);
});

it('joins selected properties with commas', function (): void {
    $params = ODataQueryBuilder::make()
        ->select('FirstName', 'LastName', 'Email')
        ->toArray();

    expect($params)->toBe(['$select' => 'FirstName,LastName,Email']);
});

it('appends across multiple select calls', function (): void {
    $params = ODataQueryBuilder::make()
        ->select('A', 'B')
        ->select('C')
        ->toArray();

    expect($params['$select'])->toBe('A,B,C');
});

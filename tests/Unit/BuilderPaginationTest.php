<?php

declare(strict_types=1);

use SimpleSquid\SaloonOData\Exceptions\InvalidODataQueryException;
use SimpleSquid\SaloonOData\ODataQueryBuilder;

it('renders top, skip and skiptoken', function (): void {
    $params = ODataQueryBuilder::make()
        ->top(10)
        ->skip(20)
        ->skipToken('cursor-abc')
        ->toArray();

    expect($params)->toMatchArray([
        '$top' => '10',
        '$skip' => '20',
        '$skiptoken' => 'cursor-abc',
    ]);
});

it('rejects negative top or skip', function (int $top, int $skip): void {
    ODataQueryBuilder::make()->top($top)->skip($skip);
})->with([
    'negative top' => [-1, 0],
    'negative skip' => [0, -1],
])->throws(InvalidODataQueryException::class);

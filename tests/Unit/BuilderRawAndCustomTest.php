<?php

declare(strict_types=1);

use SimpleSquid\SaloonOData\Exceptions\InvalidODataQueryException;
use SimpleSquid\SaloonOData\ODataQueryBuilder;

it('passes custom non-system params through verbatim', function (): void {
    $params = ODataQueryBuilder::make()
        ->param('apikey', 'secret')
        ->param('debug', true)
        ->toArray();

    expect($params)->toMatchArray([
        'apikey' => 'secret',
        'debug' => 'true',
    ]);
});

it('refuses $-prefixed keys via param() to protect system options', function (): void {
    ODataQueryBuilder::make()->param('$select', 'Name');
})->throws(InvalidODataQueryException::class);

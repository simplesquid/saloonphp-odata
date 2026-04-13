<?php

declare(strict_types=1);

use SimpleSquid\SaloonOData\Filter\FilterBuilder;
use SimpleSquid\SaloonOData\ODataQueryBuilder;

it('whereEquals is a shorthand for eq', function (): void {
    $params = ODataQueryBuilder::make()
        ->filter(fn (FilterBuilder $f) => $f->whereEquals('Status', 'Active'))
        ->toArray();

    expect($params['$filter'])->toBe("Status eq 'Active'");
});

it('whereNotEquals is a shorthand for ne', function (): void {
    $params = ODataQueryBuilder::make()
        ->filter(fn (FilterBuilder $f) => $f->whereNotEquals('Status', 'Active'))
        ->toArray();

    expect($params['$filter'])->toBe("Status ne 'Active'");
});

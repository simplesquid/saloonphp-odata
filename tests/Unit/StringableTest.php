<?php

declare(strict_types=1);

use SimpleSquid\SaloonOData\ODataQueryBuilder;

it('stringifies to a URL-encoded query string', function (): void {
    $string = (string) ODataQueryBuilder::make()
        ->select('FirstName', 'LastName')
        ->top(10);

    expect($string)
        ->toContain('%24select=FirstName%2CLastName')
        ->toContain('%24top=10');
});

it('toQueryString matches __toString', function (): void {
    $builder = ODataQueryBuilder::make()->select('A')->top(5);

    expect($builder->toQueryString())->toBe((string) $builder);
});

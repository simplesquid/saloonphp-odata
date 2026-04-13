<?php

declare(strict_types=1);

use SimpleSquid\SaloonOData\Enums\ODataVersion;
use SimpleSquid\SaloonOData\ODataQueryBuilder;

it('clones into an independent builder', function (): void {
    $base = ODataQueryBuilder::make()->select('A')->top(5);

    $forked = $base->clone()->top(10);

    expect($base->toArray()['$top'])->toBe('5')
        ->and($forked->toArray()['$top'])->toBe('10');
});

it('fresh() returns a new empty builder with the same version', function (): void {
    $base = ODataQueryBuilder::make(ODataVersion::V3)
        ->select('A')
        ->top(5);

    $fresh = $base->fresh();

    expect($fresh->toArray())->toBe([])
        ->and($fresh->version)->toBe(ODataVersion::V3);
});

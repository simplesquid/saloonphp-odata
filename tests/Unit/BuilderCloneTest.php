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

it('cloned builders do not share filter or expand state', function (): void {
    $base = ODataQueryBuilder::make()
        ->select('A')
        ->filter(fn ($f) => $f->whereEquals('X', 1))
        ->expand('Trips');

    $forked = $base->clone()
        ->select('B')
        ->filter(fn ($f) => $f->whereEquals('Y', 2))
        ->expand('Friends');

    expect($base->toArray())->toMatchArray([
        '$select' => 'A',
        '$filter' => 'X eq 1',
        '$expand' => 'Trips',
    ])->and($forked->toArray())->toMatchArray([
        '$select' => 'A,B',
        '$expand' => 'Trips,Friends',
    ]);

    // Filter accumulation: the forked builder gets both fragments.
    expect($forked->toArray()['$filter'])->toBe('(X eq 1) and (Y eq 2)');
});

it('fresh() returns a new empty builder with the same version', function (): void {
    $base = ODataQueryBuilder::make(ODataVersion::V3)
        ->select('A')
        ->top(5);

    $fresh = $base->fresh();

    expect($fresh->toArray())->toBe([])
        ->and($fresh->version)->toBe(ODataVersion::V3);
});

<?php

declare(strict_types=1);

use SimpleSquid\SaloonOData\ODataQueryBuilder;
use SimpleSquid\SaloonOData\Tests\Fixtures\TestConnector;
use SimpleSquid\SaloonOData\Tests\Fixtures\TestRequest;

it('merges its params into a Saloon Request query bag', function (): void {
    $request = new TestRequest;

    ODataQueryBuilder::make()
        ->select('A', 'B')
        ->top(5)
        ->applyTo($request);

    expect($request->query()->all())->toBe([
        '$select' => 'A,B',
        '$top' => '5',
    ]);
});

it('merges into a PendingRequest', function (): void {
    $connector = new TestConnector;
    $request = new TestRequest;
    $pending = $connector->createPendingRequest($request);

    ODataQueryBuilder::make()->select('A')->applyTo($pending);

    expect($pending->query()->all())->toMatchArray(['$select' => 'A']);
});

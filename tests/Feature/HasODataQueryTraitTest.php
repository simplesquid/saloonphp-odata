<?php

declare(strict_types=1);

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use SimpleSquid\SaloonOData\Filter\FilterBuilder;
use SimpleSquid\SaloonOData\Tests\Fixtures\TestConnector;
use SimpleSquid\SaloonOData\Tests\Fixtures\TestRequest;

it('merges the built query into the sent request', function (): void {
    $mock = new MockClient([MockResponse::make(['value' => []])]);
    $connector = new TestConnector;
    $connector->withMockClient($mock);

    $request = new TestRequest;
    $request->odataQuery()
        ->select('FirstName', 'LastName')
        ->filter(fn (FilterBuilder $f) => $f->where('Age', 'gt', 30))
        ->top(5);

    $connector->send($request);

    $sent = $mock->getLastPendingRequest();
    expect($sent)->not->toBeNull();

    \assert($sent !== null);
    $params = $sent->query()->all();

    expect($params)->toMatchArray([
        '$select' => 'FirstName,LastName',
        '$filter' => 'Age gt 30',
        '$top' => '5',
    ]);
});

it('does not override params explicitly added by the user', function (): void {
    $mock = new MockClient([MockResponse::make([])]);
    $connector = new TestConnector;
    $connector->withMockClient($mock);

    $request = new TestRequest;
    $request->query()->add('apikey', 'user-secret');
    $request->odataQuery()->top(5);

    $connector->send($request);

    expect($mock->getLastPendingRequest()?->query()->all())->toMatchArray([
        'apikey' => 'user-secret',
        '$top' => '5',
    ]);
});

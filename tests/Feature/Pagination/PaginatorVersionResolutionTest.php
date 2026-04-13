<?php

declare(strict_types=1);

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use SimpleSquid\SaloonOData\Enums\ODataVersion;
use SimpleSquid\SaloonOData\Pagination\ODataPaginator;
use SimpleSquid\SaloonOData\Support\AttributeReader;
use SimpleSquid\SaloonOData\Tests\Fixtures\TestConnector;
use SimpleSquid\SaloonOData\Tests\Fixtures\TestRequest;
use SimpleSquid\SaloonOData\Tests\Fixtures\V3Connector;
use SimpleSquid\SaloonOData\Tests\Fixtures\V3Request;

beforeEach(fn () => AttributeReader::flush());

it('resolves the version from the request attribute when none is passed', function (): void {
    $mock = new MockClient([MockResponse::make([
        'value' => [],
        '__next' => 'https://api.example.test/People?$skiptoken=x',
    ])]);
    $connector = new TestConnector;
    $connector->withMockClient($mock);

    $paginator = new ODataPaginator($connector, new V3Request);

    expect($paginator->version)->toBe(ODataVersion::V3);
});

it('resolves the version from the connector attribute as a fallback', function (): void {
    $connector = new V3Connector;

    $paginator = new ODataPaginator($connector, new TestRequest);

    expect($paginator->version)->toBe(ODataVersion::V3);
});

it('honours an explicit version argument over attributes', function (): void {
    $paginator = new ODataPaginator(new V3Connector, new V3Request, ODataVersion::V4);

    expect($paginator->version)->toBe(ODataVersion::V4);
});

it('defaults to v4 when nothing is configured', function (): void {
    $paginator = new ODataPaginator(new TestConnector, new TestRequest);

    expect($paginator->version)->toBe(ODataVersion::V4);
});

it('picks up an explicit ->withVersion() on a HasODataQuery request', function (): void {
    $request = new TestRequest;
    $request->odataQuery()->withVersion(ODataVersion::V3);

    $paginator = new ODataPaginator(new TestConnector, $request);

    expect($paginator->version)->toBe(ODataVersion::V3);
});

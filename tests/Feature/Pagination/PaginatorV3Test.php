<?php

declare(strict_types=1);

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use SimpleSquid\SaloonOData\Enums\ODataVersion;
use SimpleSquid\SaloonOData\Pagination\ODataPaginator;
use SimpleSquid\SaloonOData\Tests\Fixtures\TestConnector;
use SimpleSquid\SaloonOData\Tests\Fixtures\TestRequest;

it('walks v3 JSON-Light __next', function (): void {
    $mock = new MockClient([
        MockResponse::make([
            'value' => [['id' => 1]],
            '__next' => 'https://api.example.test/People?$skiptoken=60',
        ]),
        MockResponse::make([
            'value' => [['id' => 2]],
        ]),
    ]);
    $connector = new TestConnector;
    $connector->withMockClient($mock);

    $items = iterator_to_array(
        (new ODataPaginator($connector, new TestRequest, ODataVersion::V3))->items(),
        preserve_keys: false,
    );

    expect(array_column($items, 'id'))->toBe([1, 2]);
});

it('walks v3 JSON-Verbose d.__next and reads d.results', function (): void {
    $mock = new MockClient([
        MockResponse::make([
            'd' => [
                'results' => [['id' => 1], ['id' => 2]],
                '__next' => 'https://api.example.test/People?$skiptoken=60',
            ],
        ]),
        MockResponse::make([
            'd' => [
                'results' => [['id' => 3]],
            ],
        ]),
    ]);
    $connector = new TestConnector;
    $connector->withMockClient($mock);

    $items = iterator_to_array(
        (new ODataPaginator($connector, new TestRequest, ODataVersion::V3))->items(),
        preserve_keys: false,
    );

    expect(array_column($items, 'id'))->toBe([1, 2, 3]);
});

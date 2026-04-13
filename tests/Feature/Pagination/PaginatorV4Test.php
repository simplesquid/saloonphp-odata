<?php

declare(strict_types=1);

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use SimpleSquid\SaloonOData\Enums\ODataVersion;
use SimpleSquid\SaloonOData\Pagination\ODataPaginator;
use SimpleSquid\SaloonOData\Tests\Fixtures\TestConnector;
use SimpleSquid\SaloonOData\Tests\Fixtures\TestRequest;

it('walks @odata.nextLink across pages and stops when absent', function (): void {
    $mock = new MockClient([
        MockResponse::make([
            'value' => [['id' => 1], ['id' => 2]],
            '@odata.nextLink' => 'https://api.example.test/People?$skiptoken=page2',
        ]),
        MockResponse::make([
            'value' => [['id' => 3]],
            '@odata.nextLink' => 'https://api.example.test/People?$skiptoken=page3',
        ]),
        MockResponse::make([
            'value' => [['id' => 4]],
        ]),
    ]);

    $connector = new TestConnector;
    $connector->withMockClient($mock);

    $paginator = new ODataPaginator($connector, new TestRequest, ODataVersion::V4);

    $items = iterator_to_array($paginator->items(), preserve_keys: false);

    expect($items)->toHaveCount(4)
        ->and(array_column($items, 'id'))->toBe([1, 2, 3, 4]);
});

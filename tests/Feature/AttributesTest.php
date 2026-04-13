<?php

declare(strict_types=1);

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use SimpleSquid\SaloonOData\Filter\FilterBuilder;
use SimpleSquid\SaloonOData\Support\AttributeReader;
use SimpleSquid\SaloonOData\Tests\Fixtures\AttributedRequest;
use SimpleSquid\SaloonOData\Tests\Fixtures\TestConnector;

beforeEach(fn () => AttributeReader::flush());

it('uses #[ODataEntity] to resolve the endpoint', function (): void {
    $mock = new MockClient([MockResponse::make([])]);
    $connector = new TestConnector;
    $connector->withMockClient($mock);

    $request = new AttributedRequest;
    $connector->send($request);

    expect($mock->getLastPendingRequest()?->getUrl())
        ->toContain('/SalesInvoices');
});

it('layers runtime filters over declarative defaults', function (): void {
    $mock = new MockClient([MockResponse::make([])]);
    $connector = new TestConnector;
    $connector->withMockClient($mock);

    $request = new AttributedRequest;
    $request->odataQuery()->filter(
        fn (FilterBuilder $f) => $f->where('InvoiceDate', 'gt', new DateTimeImmutable('2025-01-01T00:00:00Z')),
    );

    $connector->send($request);

    $params = $mock->getLastPendingRequest()?->query()->all() ?? [];

    expect($params)->toMatchArray([
        '$select' => 'ID,InvoiceDate,AmountDC',
        '$top' => '50',
        '$inlinecount' => 'allpages',
        '$filter' => "InvoiceDate gt datetime'2025-01-01T00:00:00'",
    ]);
});

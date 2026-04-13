<?php

declare(strict_types=1);

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use SimpleSquid\SaloonOData\Support\AttributeReader;
use SimpleSquid\SaloonOData\Tests\Fixtures\AttributedRequest;
use SimpleSquid\SaloonOData\Tests\Fixtures\TestConnector;

beforeEach(fn () => AttributeReader::flush());

it('replaceSelect overrides the attribute-supplied $select', function (): void {
    $mock = new MockClient([MockResponse::make([])]);
    $connector = new TestConnector;
    $connector->withMockClient($mock);

    $request = new AttributedRequest;
    // Attribute selects ID, InvoiceDate, AmountDC. Replace with a single field.
    $request->odataQuery()->replaceSelect('ID');

    $connector->send($request);

    expect($mock->getLastPendingRequest()?->query()->all())
        ->toMatchArray(['$select' => 'ID']);
});

it('clearSelect drops the attribute-supplied $select entirely', function (): void {
    $mock = new MockClient([MockResponse::make([])]);
    $connector = new TestConnector;
    $connector->withMockClient($mock);

    $request = new AttributedRequest;
    $request->odataQuery()->clearSelect();

    $connector->send($request);

    expect($mock->getLastPendingRequest()?->query()->all())
        ->not->toHaveKey('$select');
});

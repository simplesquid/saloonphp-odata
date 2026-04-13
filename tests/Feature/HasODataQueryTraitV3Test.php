<?php

declare(strict_types=1);

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use SimpleSquid\SaloonOData\Support\AttributeReader;
use SimpleSquid\SaloonOData\Tests\Fixtures\TestConnector;
use SimpleSquid\SaloonOData\Tests\Fixtures\V3Request;

beforeEach(fn () => AttributeReader::flush());

it('reads the version attribute from a v3-marked Request', function (): void {
    $mock = new MockClient([MockResponse::make([])]);
    $connector = new TestConnector;
    $connector->withMockClient($mock);

    $request = new V3Request;
    $request->odataQuery()->count();

    $connector->send($request);

    expect($mock->getLastPendingRequest()?->query()->all())
        ->toMatchArray(['$inlinecount' => 'allpages']);
});

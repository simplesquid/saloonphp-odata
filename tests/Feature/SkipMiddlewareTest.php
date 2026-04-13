<?php

declare(strict_types=1);

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use SimpleSquid\SaloonOData\Tests\Fixtures\TestConnector;
use SimpleSquid\SaloonOData\Tests\Fixtures\TestRequest;

it('does not register middleware when no OData params and no attributes apply', function (): void {
    $mock = new MockClient([MockResponse::make([])]);
    $connector = new TestConnector;
    $connector->withMockClient($mock);

    // Never touch odataQuery() — request goes out plain.
    $connector->send(new TestRequest);

    expect($mock->getLastPendingRequest()?->query()->all())->toBe([]);
});

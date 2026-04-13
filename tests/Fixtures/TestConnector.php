<?php

declare(strict_types=1);

namespace SimpleSquid\SaloonOData\Tests\Fixtures;

use Saloon\Http\Connector;

class TestConnector extends Connector
{
    public function resolveBaseUrl(): string
    {
        return 'https://api.example.test';
    }
}

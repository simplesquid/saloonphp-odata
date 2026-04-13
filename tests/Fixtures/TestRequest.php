<?php

declare(strict_types=1);

namespace SimpleSquid\SaloonOData\Tests\Fixtures;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\PaginationPlugin\Contracts\Paginatable;
use SimpleSquid\SaloonOData\Concerns\HasODataQuery;

class TestRequest extends Request implements Paginatable
{
    use HasODataQuery;

    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/'.($this->odataEntity() ?? 'People');
    }
}

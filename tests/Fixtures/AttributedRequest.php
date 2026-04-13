<?php

declare(strict_types=1);

namespace SimpleSquid\SaloonOData\Tests\Fixtures;

use SimpleSquid\SaloonOData\Attributes\DefaultODataQuery;
use SimpleSquid\SaloonOData\Attributes\ODataEntity;
use SimpleSquid\SaloonOData\Attributes\ODataVersion;
use SimpleSquid\SaloonOData\Enums\ODataVersion as VersionEnum;

#[ODataVersion(VersionEnum::V3)]
#[ODataEntity('SalesInvoices')]
#[DefaultODataQuery(
    select: ['ID', 'InvoiceDate', 'AmountDC'],
    top: 50,
    count: true,
)]
class AttributedRequest extends TestRequest {}

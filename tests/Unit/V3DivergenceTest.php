<?php

declare(strict_types=1);

use SimpleSquid\SaloonOData\Enums\ODataVersion;
use SimpleSquid\SaloonOData\Enums\SortDirection;
use SimpleSquid\SaloonOData\Filter\FilterBuilder;
use SimpleSquid\SaloonOData\ODataQueryBuilder;

it('produces an Exact Online-style v3 query', function (): void {
    $params = ODataQueryBuilder::make(ODataVersion::V3)
        ->select('ID', 'InvoiceDate', 'AmountDC')
        ->filter(fn (FilterBuilder $f) => $f
            ->where('InvoiceDate', 'gt', new DateTimeImmutable('2025-01-01T00:00:00Z'))
            ->and()
            ->contains('YourRef', 'PO-'))
        ->orderBy('InvoiceDate', SortDirection::Desc)
        ->top(60)
        ->count()
        ->skipToken('60')
        ->toArray();

    expect($params)->toBe([
        '$select' => 'ID,InvoiceDate,AmountDC',
        '$filter' => "InvoiceDate gt datetime'2025-01-01T00:00:00' and substringof('PO-',YourRef)",
        '$orderby' => 'InvoiceDate desc',
        '$top' => '60',
        '$skiptoken' => '60',
        '$inlinecount' => 'allpages',
    ]);
});

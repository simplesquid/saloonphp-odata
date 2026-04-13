<?php

declare(strict_types=1);

use SimpleSquid\SaloonOData\Enums\ODataVersion;
use SimpleSquid\SaloonOData\ODataQueryBuilder;
use SimpleSquid\SaloonOData\Support\AttributeReader;
use SimpleSquid\SaloonOData\Tests\Fixtures\AttributedRequest;
use SimpleSquid\SaloonOData\Tests\Fixtures\TestRequest;

beforeEach(fn () => AttributeReader::flush());

it('reads the version attribute from the class', function (): void {
    expect(AttributeReader::version(new AttributedRequest))->toBe(ODataVersion::V3);
});

it('returns null when no version attribute is set', function (): void {
    expect(AttributeReader::version(new TestRequest))->toBeNull();
});

it('reads the entity attribute', function (): void {
    expect(AttributeReader::entity(new AttributedRequest))->toBe('SalesInvoices');
});

it('applies #[DefaultODataQuery] to a builder', function (): void {
    $builder = ODataQueryBuilder::make(ODataVersion::V3);
    AttributeReader::applyDefaults(new AttributedRequest, $builder);

    $params = $builder->toArray();

    expect($params)->toMatchArray([
        '$select' => 'ID,InvoiceDate,AmountDC',
        '$top' => '50',
        '$inlinecount' => 'allpages',
    ]);
});

<?php

declare(strict_types=1);

use SimpleSquid\SaloonOData\Enums\ODataVersion;
use SimpleSquid\SaloonOData\Exceptions\UnsupportedInVersionException;
use SimpleSquid\SaloonOData\Filter\FilterBuilder;
use SimpleSquid\SaloonOData\ODataQueryBuilder;

it('throws when the in operator is used on v3', function (): void {
    ODataQueryBuilder::make(ODataVersion::V3)->filter(
        fn (FilterBuilder $f) => $f->in('Status', ['A', 'B']),
    );
})->throws(UnsupportedInVersionException::class);

it('throws when the has operator is used on v3', function (): void {
    ODataQueryBuilder::make(ODataVersion::V3)->filter(
        fn (FilterBuilder $f) => $f->has('Roles', 'Admin'),
    );
})->throws(UnsupportedInVersionException::class);

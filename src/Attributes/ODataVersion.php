<?php

declare(strict_types=1);

namespace SimpleSquid\SaloonOData\Attributes;

use Attribute;
use SimpleSquid\SaloonOData\Enums\ODataVersion as VersionEnum;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class ODataVersion
{
    public function __construct(public VersionEnum $version) {}
}

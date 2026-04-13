<?php

declare(strict_types=1);

namespace SimpleSquid\SaloonOData\Attributes;

use Attribute;
use SimpleSquid\SaloonOData\Enums\ODataVersion;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class UsesODataVersion
{
    public function __construct(public ODataVersion $version) {}
}

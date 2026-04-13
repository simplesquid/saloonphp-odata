<?php

declare(strict_types=1);

namespace SimpleSquid\SaloonOData\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class ODataEntity
{
    public function __construct(public string $name) {}
}

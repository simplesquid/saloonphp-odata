<?php

declare(strict_types=1);

namespace SimpleSquid\SaloonOData\Order;

use SimpleSquid\SaloonOData\Enums\SortDirection;

final readonly class OrderByClause
{
    public function __construct(
        public string $property,
        public SortDirection $direction = SortDirection::Asc,
    ) {}

    public function render(): string
    {
        return $this->property.' '.$this->direction->value;
    }
}

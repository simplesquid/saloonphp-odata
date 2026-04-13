<?php

declare(strict_types=1);

namespace SimpleSquid\SaloonOData\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class DefaultODataQuery
{
    /**
     * @param  list<string>  $select
     * @param  list<string>  $expand
     * @param  array<string, 'asc'|'desc'>  $orderBy
     * @param  array<string, scalar>  $params
     */
    public function __construct(
        public array $select = [],
        public array $expand = [],
        public array $orderBy = [],
        public ?int $top = null,
        public ?int $skip = null,
        public bool $count = false,
        public ?string $search = null,
        public ?string $format = null,
        public ?string $filterRaw = null,
        public array $params = [],
    ) {}
}

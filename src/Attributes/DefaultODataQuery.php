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
     * @param  bool  $count  Set to true to emit `$count=true` (v4) / `$inlinecount=allpages` (v3).
     *                       false (the default) skips the parameter entirely; if you need to
     *                       explicitly emit `$count=false`, call `->count(false)` on the builder.
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

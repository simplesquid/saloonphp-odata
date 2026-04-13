<?php

declare(strict_types=1);

namespace SimpleSquid\SaloonOData\Expand;

use Closure;
use SimpleSquid\SaloonOData\Enums\ODataVersion;
use SimpleSquid\SaloonOData\Enums\SortDirection;
use SimpleSquid\SaloonOData\Exceptions\InvalidODataQueryException;
use SimpleSquid\SaloonOData\Exceptions\UnsupportedInVersionException;
use SimpleSquid\SaloonOData\Filter\FilterBuilder;
use SimpleSquid\SaloonOData\Support\PropertyName;

/**
 * Closure-target for ODataQueryBuilder::expand().
 *
 * Renders the v4 nested-options form: `Trips($select=Name;$top=5)`.
 * Calling any of these methods on a v3 builder is an error — v3 only
 * supports flat `$expand=A,A/B` paths.
 */
final class ExpandBuilder
{
    /** @var list<string> */
    private array $select = [];

    /** @var list<string> */
    private array $expand = [];

    /** @var list<string> */
    private array $orderBy = [];

    private ?int $top = null;

    private ?int $skip = null;

    private ?bool $count = null;

    /** @var Closure(FilterBuilder): mixed|null */
    private ?Closure $filter = null;

    public function __construct(public readonly ODataVersion $version)
    {
        if ($this->version === ODataVersion::V3) {
            throw new UnsupportedInVersionException(
                'Nested $expand options are not supported in OData v3. '
                .'Use the flat path syntax: ->expand("Trips") or ->expand("Trips/Stops").',
            );
        }
    }

    public function select(string ...$properties): self
    {
        foreach ($properties as $property) {
            PropertyName::assert($property);
            $this->select[] = $property;
        }

        return $this;
    }

    public function expand(string $navigation): self
    {
        PropertyName::assert($navigation);
        $this->expand[] = $navigation;

        return $this;
    }

    /**
     * @param  Closure(FilterBuilder): mixed  $build
     */
    public function filter(Closure $build): self
    {
        $this->filter = $build;

        return $this;
    }

    public function orderBy(string $property, SortDirection|string $direction = SortDirection::Asc): self
    {
        PropertyName::assert($property);
        $this->orderBy[] = $property.' '.SortDirection::coerce($direction)->value;

        return $this;
    }

    public function orderByDesc(string $property): self
    {
        return $this->orderBy($property, SortDirection::Desc);
    }

    public function top(int $top): self
    {
        if ($top < 0) {
            throw new InvalidODataQueryException('$top must be non-negative.');
        }

        $this->top = $top;

        return $this;
    }

    public function skip(int $skip): self
    {
        if ($skip < 0) {
            throw new InvalidODataQueryException('$skip must be non-negative.');
        }

        $this->skip = $skip;

        return $this;
    }

    public function count(bool $include = true): self
    {
        $this->count = $include;

        return $this;
    }

    /**
     * Render the inside of the parentheses. Returns null when no nested
     * options were configured (caller should emit the bare navigation name).
     */
    public function render(): ?string
    {
        $parts = [];

        if ($this->select !== []) {
            $parts[] = '$select='.implode(',', $this->select);
        }
        if ($this->filter !== null) {
            $filterBuilder = new FilterBuilder($this->version);
            ($this->filter)($filterBuilder);
            $rendered = $filterBuilder->render();
            if ($rendered !== '') {
                $parts[] = '$filter='.$rendered;
            }
        }
        if ($this->orderBy !== []) {
            $parts[] = '$orderby='.implode(',', $this->orderBy);
        }
        if ($this->top !== null) {
            $parts[] = '$top='.$this->top;
        }
        if ($this->skip !== null) {
            $parts[] = '$skip='.$this->skip;
        }
        if ($this->count !== null) {
            $parts[] = '$count='.($this->count ? 'true' : 'false');
        }
        if ($this->expand !== []) {
            $parts[] = '$expand='.implode(',', $this->expand);
        }

        return $parts === [] ? null : implode(';', $parts);
    }
}

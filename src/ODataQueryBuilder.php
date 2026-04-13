<?php

declare(strict_types=1);

namespace SimpleSquid\SaloonOData;

use Closure;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;
use SimpleSquid\SaloonOData\Enums\ODataVersion;
use SimpleSquid\SaloonOData\Enums\SortDirection;
use SimpleSquid\SaloonOData\Exceptions\InvalidODataQueryException;
use SimpleSquid\SaloonOData\Exceptions\UnsupportedInVersionException;
use SimpleSquid\SaloonOData\Expand\ExpandBuilder;
use SimpleSquid\SaloonOData\Filter\FilterBuilder;
use SimpleSquid\SaloonOData\Order\OrderByClause;
use Stringable;

/**
 * Fluent OData query-string builder.
 *
 * Supports OData v3 and v4. Constructed via {@see make()}; usable on its own,
 * inside a Saloon Request via {@see Concerns\HasODataQuery}, or applied to a
 * Request/PendingRequest with {@see applyTo()}.
 */
final class ODataQueryBuilder implements Stringable
{
    /** Asymmetric visibility (PHP 8.4): readable everywhere, only set internally. */
    public private(set) ODataVersion $version;

    /** @var list<string> */
    private array $select = [];

    private ?string $filter = null;

    /** @var list<array{0: string, 1: ?ExpandBuilder}> */
    private array $expand = [];

    /** @var list<OrderByClause> */
    private array $orderBy = [];

    private ?int $top = null;

    private ?int $skip = null;

    private ?bool $count = null;

    private ?string $skipToken = null;

    private ?string $search = null;

    private ?string $format = null;

    /** @var array<string, scalar> */
    private array $params = [];

    private function __construct(ODataVersion $version)
    {
        $this->version = $version;
    }

    public static function make(ODataVersion $version = ODataVersion::V4): self
    {
        return new self($version);
    }

    public function select(string ...$properties): self
    {
        foreach ($properties as $property) {
            $this->select[] = $property;
        }

        return $this;
    }

    /**
     * @param  Closure(FilterBuilder): mixed  $build
     */
    public function filter(Closure $build): self
    {
        $filter = new FilterBuilder($this->version);
        $build($filter);

        $rendered = $filter->render();

        $this->filter = $this->filter === null || $this->filter === ''
            ? $rendered
            : '('.$this->filter.') and ('.$rendered.')';

        return $this;
    }

    /**
     * Append a raw, pre-encoded filter expression.
     */
    public function filterRaw(string $expression): self
    {
        $this->filter = $this->filter === null || $this->filter === ''
            ? $expression
            : '('.$this->filter.') and ('.$expression.')';

        return $this;
    }

    /**
     * Add a navigation property to $expand.
     *
     * Pass a closure to use OData v4 nested options (`Trips($select=Name)`).
     * Calling with a closure on a v3 builder throws.
     *
     * @param  Closure(ExpandBuilder): mixed|null  $build
     *
     * @throws UnsupportedInVersionException
     */
    public function expand(string $navigation, ?Closure $build = null): self
    {
        if ($build === null) {
            $this->expand[] = [$navigation, null];

            return $this;
        }

        $expand = new ExpandBuilder($this->version);
        $build($expand);

        $this->expand[] = [$navigation, $expand];

        return $this;
    }

    public function orderBy(string $property, SortDirection|string $direction = SortDirection::Asc): self
    {
        $this->orderBy[] = new OrderByClause($property, SortDirection::coerce($direction));

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

    public function skipToken(string $token): self
    {
        $this->skipToken = $token;

        return $this;
    }

    /**
     * @throws UnsupportedInVersionException
     */
    public function search(string $term): self
    {
        if ($this->version === ODataVersion::V3) {
            throw new UnsupportedInVersionException('$search is not available in OData v3.');
        }

        $this->search = $term;

        return $this;
    }

    public function format(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function param(string $key, string|int|float|bool $value): self
    {
        if (str_starts_with($key, '$')) {
            throw new InvalidODataQueryException(
                'Use the dedicated builder method for system query options ($-prefixed keys).',
            );
        }

        $this->params[$key] = $value;

        return $this;
    }

    /**
     * Render to a flat query map suitable for Saloon's ArrayStore::merge().
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $params = [];

        if ($this->select !== []) {
            $params['$select'] = implode(',', $this->select);
        }

        if ($this->filter !== null && $this->filter !== '') {
            $params['$filter'] = $this->filter;
        }

        if ($this->expand !== []) {
            $params['$expand'] = $this->renderExpand();
        }

        if ($this->orderBy !== []) {
            $params['$orderby'] = implode(',', array_map(
                static fn (OrderByClause $clause): string => $clause->render(),
                $this->orderBy,
            ));
        }

        if ($this->top !== null) {
            $params['$top'] = (string) $this->top;
        }

        if ($this->skip !== null) {
            $params['$skip'] = (string) $this->skip;
        }

        if ($this->skipToken !== null) {
            $params['$skiptoken'] = $this->skipToken;
        }

        if ($this->count !== null) {
            $key = $this->version === ODataVersion::V3 ? '$inlinecount' : '$count';
            $value = $this->version === ODataVersion::V3
                ? ($this->count ? 'allpages' : 'none')
                : ($this->count ? 'true' : 'false');
            $params[$key] = $value;
        }

        if ($this->search !== null) {
            $params['$search'] = $this->search;
        }

        if ($this->format !== null) {
            $params['$format'] = $this->format;
        }

        foreach ($this->params as $key => $value) {
            $params[$key] = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
        }

        return $params;
    }

    /**
     * Render to a URL-encoded query string (no leading `?`).
     */
    public function toQueryString(): string
    {
        return http_build_query($this->toArray(), '', '&', PHP_QUERY_RFC3986);
    }

    public function __toString(): string
    {
        return $this->toQueryString();
    }

    /**
     * Merge the built params into the target's query bag.
     */
    public function applyTo(Request|PendingRequest $target): void
    {
        $target->query()->merge($this->toArray());
    }

    public function clone(): self
    {
        return clone $this;
    }

    /**
     * A new empty builder using the same version as this one.
     */
    public function fresh(): self
    {
        return self::make($this->version);
    }

    private function renderExpand(): string
    {
        $parts = [];

        foreach ($this->expand as [$navigation, $builder]) {
            if ($builder === null) {
                $parts[] = $navigation;

                continue;
            }

            $inner = $builder->render();
            $parts[] = $inner === null ? $navigation : "{$navigation}({$inner})";
        }

        return implode(',', $parts);
    }
}

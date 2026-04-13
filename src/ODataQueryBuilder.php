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
use SimpleSquid\SaloonOData\Support\PropertyName;
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

    /**
     * Filter fragments to AND together at render time. A closure is rendered
     * against a fresh FilterBuilder using the *current* version, so a late
     * version switch (via withVersion()) renders correctly. A string is a
     * pre-encoded fragment from filterRaw() and is emitted as-is.
     *
     * @var list<string|Closure(FilterBuilder): mixed>
     */
    private array $filterFragments = [];

    /** @var list<array{0: string, 1: ?Closure(ExpandBuilder): mixed}> */
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

    /**
     * Late-bind the version. Useful for plugins that resolve the version from
     * a Connector at request-boot time (after the user has already chained
     * methods on the builder). Filters and nested $expand are rendered lazily,
     * so a late switch is safe; only `filterRaw()` content is version-baked.
     */
    public function withVersion(ODataVersion $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function select(string ...$properties): self
    {
        foreach ($properties as $property) {
            PropertyName::assert($property);
            $this->select[] = $property;
        }

        return $this;
    }

    /**
     * Replace any existing $select with the given properties (including any
     * inherited from {@see Attributes\DefaultODataQuery}).
     */
    public function replaceSelect(string ...$properties): self
    {
        $this->select = [];

        return $this->select(...$properties);
    }

    public function clearSelect(): self
    {
        $this->select = [];

        return $this;
    }

    /**
     * @param  Closure(FilterBuilder): mixed  $build
     */
    public function filter(Closure $build): self
    {
        $this->filterFragments[] = $build;

        return $this;
    }

    /**
     * Append a raw, pre-encoded filter expression.
     *
     * @security Caller is fully responsible for the contents of `$expression`.
     * The package does NOT escape, validate, or re-render this string. Never
     * pass untrusted input directly — build with {@see filter()} closures
     * instead, which use the version-aware Literal encoder.
     */
    public function filterRaw(string $expression): self
    {
        $this->filterFragments[] = $expression;

        return $this;
    }

    public function clearFilter(): self
    {
        $this->filterFragments = [];

        return $this;
    }

    /**
     * Discard any existing filter fragments, then apply the given closure.
     *
     * @param  Closure(FilterBuilder): mixed  $build
     */
    public function replaceFilter(Closure $build): self
    {
        return $this->clearFilter()->filter($build);
    }

    /**
     * Add a navigation property to $expand.
     *
     * Pass a closure to use OData v4 nested options (`Trips($select=Name)`).
     * Closure form throws {@see UnsupportedInVersionException} at render time
     * if the builder is v3.
     *
     * @param  Closure(ExpandBuilder): mixed|null  $build
     */
    public function expand(string $navigation, ?Closure $build = null): self
    {
        PropertyName::assert($navigation);
        $this->expand[] = [$navigation, $build];

        return $this;
    }

    public function clearExpand(): self
    {
        $this->expand = [];

        return $this;
    }

    /**
     * Discard any existing $expand entries, then add the given navigation.
     */
    public function replaceExpand(string $navigation, ?Closure $build = null): self
    {
        return $this->clearExpand()->expand($navigation, $build);
    }

    public function orderBy(string $property, SortDirection|string $direction = SortDirection::Asc): self
    {
        PropertyName::assert($property);
        $this->orderBy[] = new OrderByClause($property, SortDirection::coerce($direction));

        return $this;
    }

    public function orderByDesc(string $property): self
    {
        return $this->orderBy($property, SortDirection::Desc);
    }

    public function clearOrderBy(): self
    {
        $this->orderBy = [];

        return $this;
    }

    /**
     * Discard any existing $orderby clauses, then add the given one.
     */
    public function replaceOrderBy(string $property, SortDirection|string $direction = SortDirection::Asc): self
    {
        return $this->clearOrderBy()->orderBy($property, $direction);
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
     * Set the $search system option. Only valid in OData v4 — the version
     * check defers to render time so a late {@see withVersion()} call from
     * the trait can still flip a v4 builder to v3.
     */
    public function search(string $term): self
    {
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
            throw new InvalidODataQueryException(sprintf(
                'Cannot use param() for the system query option "%s". Use the dedicated method instead: '.
                '$select → select(), $filter → filter()/filterRaw(), $expand → expand(), $orderby → orderBy(), '.
                '$top/$skip/$skiptoken → top()/skip()/skipToken(), $count/$inlinecount → count(), '.
                '$search → search(), $format → format().',
                $key,
            ));
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

        $filter = $this->renderFilter();
        if ($filter !== null) {
            $params['$filter'] = $filter;
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
            if ($this->version === ODataVersion::V3) {
                throw new UnsupportedInVersionException('$search is not available in OData v3.');
            }
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

    #[\Override]
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

    private function renderFilter(): ?string
    {
        if ($this->filterFragments === []) {
            return null;
        }

        $parts = [];
        foreach ($this->filterFragments as $fragment) {
            if (is_string($fragment)) {
                if ($fragment === '') {
                    continue;
                }
                $parts[] = $fragment;

                continue;
            }

            $builder = new FilterBuilder($this->version);
            $fragment($builder);
            $rendered = $builder->render();

            if ($rendered !== '') {
                $parts[] = $rendered;
            }
        }

        if ($parts === []) {
            return null;
        }

        if (count($parts) === 1) {
            return $parts[0];
        }

        return '('.implode(') and (', $parts).')';
    }

    private function renderExpand(): string
    {
        $parts = [];

        foreach ($this->expand as [$navigation, $build]) {
            if ($build === null) {
                $parts[] = $navigation;

                continue;
            }

            $expand = new ExpandBuilder($this->version);
            $build($expand);

            $inner = $expand->render();
            $parts[] = $inner === null ? $navigation : "{$navigation}({$inner})";
        }

        return implode(',', $parts);
    }
}

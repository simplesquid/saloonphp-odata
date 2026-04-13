<?php

declare(strict_types=1);

namespace SimpleSquid\SaloonOData\Filter;

use Closure;
use SimpleSquid\SaloonOData\Enums\ComparisonOperator;
use SimpleSquid\SaloonOData\Enums\ODataVersion;
use SimpleSquid\SaloonOData\Exceptions\InvalidODataQueryException;
use SimpleSquid\SaloonOData\Exceptions\UnsupportedInVersionException;
use SimpleSquid\SaloonOData\Support\Literal;
use SimpleSquid\SaloonOData\Support\PropertyName;

/**
 * Closure-target for ODataQueryBuilder::filter().
 *
 * Builds an OData $filter expression incrementally. Logical joins (`and`,
 * `or`) are inserted automatically between clauses where needed; explicit
 * calls to {@see and()} / {@see or()} between clauses are also honoured.
 */
final class FilterBuilder
{
    /** @var list<string> */
    private array $tokens = [];

    private bool $expectsJoin = false;

    public function __construct(public readonly ODataVersion $version) {}

    public function render(): string
    {
        return implode('', $this->tokens);
    }

    /**
     * `Property op value` — e.g. `Age gt 30`.
     *
     * @throws InvalidODataQueryException
     * @throws UnsupportedInVersionException
     */
    public function where(string $property, ComparisonOperator|string $operator, mixed $value): self
    {
        PropertyName::assert($property);

        $op = ComparisonOperator::coerce($operator);
        $op->assertSupported($this->version);

        if ($op === ComparisonOperator::In) {
            if (! is_array($value)) {
                throw new InvalidODataQueryException('The "in" operator requires an array of values.');
            }

            return $this->push(sprintf(
                '%s in %s',
                $property,
                Literal::encodeCollection($value, $this->version),
            ));
        }

        return $this->push(sprintf(
            '%s %s %s',
            $property,
            $op->value,
            Literal::encode($value, $this->version),
        ));
    }

    /**
     * Shorthand for `->where($property, ComparisonOperator::Eq, $value)` —
     * by far the most common comparison.
     */
    public function whereEquals(string $property, mixed $value): self
    {
        return $this->where($property, ComparisonOperator::Eq, $value);
    }

    /**
     * Shorthand for `->where($property, ComparisonOperator::Ne, $value)`.
     */
    public function whereNotEquals(string $property, mixed $value): self
    {
        return $this->where($property, ComparisonOperator::Ne, $value);
    }

    public function and(): self
    {
        return $this->joiner(' and ');
    }

    public function or(): self
    {
        return $this->joiner(' or ');
    }

    /**
     * Wrap the next clause (or group) in `not (...)`.
     */
    public function not(): self
    {
        $this->ensureJoin();
        $this->tokens[] = 'not ';
        $this->expectsJoin = false;

        return $this;
    }

    /**
     * Wrap a nested expression in parentheses.
     *
     * @param  Closure(self): mixed  $build
     */
    public function group(Closure $build): self
    {
        $this->ensureJoin();

        $nested = new self($this->version);
        $build($nested);

        $this->tokens[] = '('.$nested->render().')';
        $this->expectsJoin = true;

        return $this;
    }

    /**
     * @param  list<mixed>  $values
     *
     * @throws UnsupportedInVersionException
     */
    public function in(string $property, array $values): self
    {
        return $this->where($property, ComparisonOperator::In, $values);
    }

    /**
     * @throws UnsupportedInVersionException
     */
    public function has(string $property, mixed $value): self
    {
        return $this->where($property, ComparisonOperator::Has, $value);
    }

    /**
     * `contains(prop, 'value')` in v4; `substringof('value', prop)` in v3.
     */
    public function contains(string $property, string $value): self
    {
        PropertyName::assert($property);
        $literal = Literal::encode($value, $this->version);

        $expression = $this->version === ODataVersion::V3
            ? "substringof({$literal},{$property})"
            : "contains({$property},{$literal})";

        return $this->push($expression);
    }

    public function startsWith(string $property, string $value): self
    {
        PropertyName::assert($property);

        return $this->push(sprintf(
            'startswith(%s,%s)',
            $property,
            Literal::encode($value, $this->version),
        ));
    }

    public function endsWith(string $property, string $value): self
    {
        PropertyName::assert($property);

        return $this->push(sprintf(
            'endswith(%s,%s)',
            $property,
            Literal::encode($value, $this->version),
        ));
    }

    /**
     * Append a raw, pre-encoded sub-expression.
     *
     * @security Caller is fully responsible for the contents of `$expression`.
     * The package does NOT escape, validate, or version-translate this string.
     * Never pass untrusted input directly.
     */
    public function raw(string $expression): self
    {
        return $this->push($expression);
    }

    private function push(string $clause): self
    {
        $this->ensureJoin();
        $this->tokens[] = $clause;
        $this->expectsJoin = true;

        return $this;
    }

    private function ensureJoin(): void
    {
        if (! $this->expectsJoin) {
            return;
        }

        $previous = end($this->tokens);

        // Don't double-insert if the caller already explicitly joined.
        if ($previous === false || ! self::endsWithJoiner($previous)) {
            $this->tokens[] = ' and ';
        }

        $this->expectsJoin = false;
    }

    private function joiner(string $token): self
    {
        if ($this->tokens === []) {
            throw new InvalidODataQueryException('Cannot start a filter expression with a logical joiner.');
        }

        $previous = end($this->tokens);

        if ($previous !== false && self::endsWithJoiner($previous)) {
            // Replace the trailing joiner so callers can switch from `and` to `or` mid-chain.
            array_pop($this->tokens);
        }

        $this->tokens[] = $token;
        $this->expectsJoin = false;

        return $this;
    }

    private static function endsWithJoiner(string $token): bool
    {
        return str_ends_with($token, ' and ') || str_ends_with($token, ' or ') || str_ends_with($token, 'not ');
    }
}

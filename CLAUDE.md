# CLAUDE.md

## What this is

A Saloon PHP plugin providing a fluent, version-aware OData query-string builder (v3 + v4) and a server-driven paginator. Designed to layer on top of any Saloon `Connector` / `Request`. The downstream user is targeting Exact Online (OData v3) but the package is generic.

## Stack

- PHP `^8.4` (uses asymmetric visibility, `#[\Override]`, readonly value objects)
- Saloon v3
- Pest 3 (with arch plugin)
- Laravel Pint
- PHPStan level 10

## Layout

```
src/
  ODataQueryBuilder.php          main fluent builder (Stringable)
  Concerns/HasODataQuery.php     Saloon plugin trait
  Filter/FilterBuilder.php       closure target for ->filter()
  Expand/ExpandBuilder.php       closure target for ->expand() (v4 only)
  Order/OrderByClause.php        readonly value object
  Enums/                         ODataVersion, ComparisonOperator, LogicalOperator, SortDirection
  Attributes/                    ODataVersion, ODataEntity, DefaultODataQuery
  Support/                       Literal (version-aware encoder), DateOnly, SkipToken, AttributeReader
  Pagination/ODataPaginator.php  Saloon Paginator: walks @odata.nextLink / __next / d.__next
  Exceptions/                    InvalidODataQueryException, UnsupportedInVersionException
tests/
  ArchTest.php
  Unit/...                        per-feature unit tests
  Feature/...                     trait + attribute + paginator feature tests
  Fixtures/                       TestConnector, TestRequest, V3Request, V3Connector, AttributedRequest
```

## Conventions

- `declare(strict_types=1);` at the top of every file.
- Final classes by default. Builders are `final`; readonly value objects use `final readonly`.
- Operators always accept `string|Enum` at the public boundary; coerce via `Enum::coerce()` and validate. Unknown strings throw `InvalidODataQueryException`.
- All literal encoding goes through `Support\Literal::encode($value, $version)`. Never inline. Version-awareness lives there.
- Version resolution order: explicit `make($v)` > `#[ODataVersion]` on Request (or parent) > `#[ODataVersion]` on Connector > default V4. The connector fallback works because filters and nested `$expand` are rendered lazily at `toArray()` time, so `withVersion()` from the trait at boot still produces correct rendering. `filterRaw()` content is the only version-baked thing — caller's responsibility.
- Validation of v3-incompatible operators (`in`, `has`, nested expand closures) defers to render time. This keeps the version-switch story consistent. Trade-off: errors surface at send time, not at definition time.
- Use `public private(set)` for fluent state that should be readable but only mutated internally (see `ODataQueryBuilder::$version`).
- `#[\Override]` on every method that overrides or implements an interface method.
- `AttributeReader` caches reflection results in static maps keyed by class name. Tests must call `AttributeReader::flush()` in `beforeEach` if they exercise multiple fixtures with overlapping classes.

## Hard rules

- No Laravel framework dependencies. The arch test enforces no `Illuminate\Foundation` / `Laravel` imports.
- No vendor-specific extensions in core (no Exact division URL helpers, no Microsoft Graph delta tokens, etc.). Only OData v3/v4 spec content. If you find yourself adding logic for a single vendor, push back or carve out a sibling package.
- No AI attribution anywhere — commits, code, docs.
- No emojis in source or docs unless the user explicitly asks.

## How to run

```bash
composer test
composer analyse
composer format
```

## What's deliberately out of scope

- Base `ODataConnector` / `ODataRequest` classes (users compose their own)
- Response DTO mapping / `@odata.context` parsing
- OData v2 (one extra `Literal` branch away if needed)
- `$batch` endpoint (separate `-batch` package if ever needed)
- `$metadata` parsing / codegen (separate `-codegen` package if ever needed)
- Auth (Saloon already handles it)

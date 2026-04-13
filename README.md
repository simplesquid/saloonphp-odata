# saloonphp-odata

A [Saloon](https://github.com/saloonphp/saloon) plugin providing a fluent, version-aware OData query builder and a server-driven paginator. Supports OData v3 and v4. Bring your own Connector — Saloon handles HTTP.

```php
use Saloon\Enums\Method;
use Saloon\Http\Request;
use SimpleSquid\SaloonOData\Concerns\HasODataQuery;
use SimpleSquid\SaloonOData\Filter\FilterBuilder;

class GetPeople extends Request
{
    use HasODataQuery;
    protected Method $method = Method::GET;
    public function resolveEndpoint(): string { return '/People'; }
}

$req = (new GetPeople)->odataQuery()
    ->select('FirstName', 'LastName')
    ->filter(fn (FilterBuilder $f) => $f
        ->whereEquals('Status', 'Active')
        ->or()
        ->where('Age', 'gt', 30))
    ->orderBy('LastName')
    ->top(10)
    ->count();

$connector->send($req);
// GET /People?$select=FirstName,LastName&$filter=Status eq 'Active' or Age gt 30&$orderby=LastName asc&$top=10&$count=true
```

## Installation

```bash
composer require simplesquid/saloonphp-odata
```

For the paginator, also install Saloon's pagination plugin:

```bash
composer require saloonphp/pagination-plugin
```

Requires PHP 8.4+ and Saloon v4.

## Usage

### As a Request trait

The trait exposes `$request->odataQuery()` returning the underlying `ODataQueryBuilder`. The builder's params are merged into the request's query string immediately before send via Saloon middleware. If the builder is never touched and no class-level attributes apply, no middleware runs.

### As a standalone builder (e.g. inside `defaultQuery()`)

```php
public function defaultQuery(): array
{
    return ODataQueryBuilder::make()
        ->select('Id', 'Name')
        ->top(50)
        ->toArray();
}
```

Or, if you have a Request/PendingRequest in hand, use `applyTo()`:

```php
ODataQueryBuilder::make()->select('Id')->applyTo($request);
```

### Declarative configuration with attributes

```php
use SimpleSquid\SaloonOData\Attributes\DefaultODataQuery;
use SimpleSquid\SaloonOData\Attributes\ODataEntity;
use SimpleSquid\SaloonOData\Attributes\UsesODataVersion;
use SimpleSquid\SaloonOData\Enums\ODataVersion;

#[UsesODataVersion(ODataVersion::V3)]
#[ODataEntity('SalesInvoices')]
#[DefaultODataQuery(
    select: ['ID', 'InvoiceDate', 'AmountDC'],
    top: 50,
    count: true,
    filterRaw: "Division eq 12345",
)]
class GetSalesInvoices extends Request
{
    use HasODataQuery;
    protected Method $method = Method::GET;
    public function resolveEndpoint(): string
    {
        return '/'.$this->odataEntity();
    }
}
```

Defaults are applied on first access to `odataQuery()`. Runtime calls layer over them; use `clearSelect()` / `replaceSelect()` / `clearFilter()` / `replaceFilter()` / `clearOrderBy()` / `replaceOrderBy()` / `clearExpand()` / `replaceExpand()` when you need to override rather than append.

The version is resolved in this order:
1. Explicit `ODataQueryBuilder::make($version)` call.
2. `#[UsesODataVersion]` on the Request class (or any parent).
3. `#[UsesODataVersion]` on the Connector class (applied at request boot).
4. Default: v4.

> Filters and nested `$expand` are rendered lazily, so a connector-level version still applies cleanly even after the user has chained `->filter(...)` on the builder. The exception is `filterRaw()` — those strings are version-baked by the caller.

## Builder reference

### Selection

```php
$q->select('FirstName', 'LastName', 'Email');
$q->replaceSelect('FirstName');   // discard previous, set anew
$q->clearSelect();                 // discard all
```

### Filtering

```php
use SimpleSquid\SaloonOData\Filter\FilterBuilder;

$q->filter(fn (FilterBuilder $f) => $f
    ->whereEquals('Status', 'Active')      // shorthand for eq
    ->whereNotEquals('Type', 'Draft')      // shorthand for ne
    ->where('Age', 'gt', 30)               // operators: eq, ne, gt, ge, lt, le, has*, in*
    ->or()                                  // switch the trailing join (default is `and`)
    ->not()                                 // negate the next clause
    ->group(fn (FilterBuilder $g) => ...)   // wrap in parentheses
    ->in('Status', ['A', 'B'])              // v4 only
    ->has('Roles', 'Admin')                 // v4 only
    ->contains('Name', 'foo')               // becomes substringof('foo', Name) on v3
    ->startsWith('Name', 'A')
    ->endsWith('Name', 'Z')
    ->raw('year(Created) eq 2025')          // pre-encoded escape hatch (UNSAFE for user input)
);

$q->filterRaw("Status eq 'Active'");        // bypass the closure entirely (UNSAFE for user input)
$q->clearFilter();                          // wipe all filter fragments
```

Operators accept a `ComparisonOperator` enum or a string; strings are validated. Property names are validated to prevent filter injection.

### Date-only and GUID literals

```php
use SimpleSquid\SaloonOData\Support\DateOnly;
use SimpleSquid\SaloonOData\Support\Literal;

// Some endpoints prefer date-only over full datetime:
$q->filter(fn ($f) => $f->where('Date', 'gt', Literal::dateOnly($dt)));
$q->filter(fn ($f) => $f->where('Date', 'gt', DateOnly::from($dt)));   // equivalent

// GUIDs render unquoted in v4 and as guid'...' in v3 — wrap explicitly:
$q->filter(fn ($f) => $f->whereEquals('Id', Literal::guid('11111111-2222-3333-4444-555555555555')));
```

### Expansion

```php
$q->expand('Trips');                       // flat (works on v3 and v4)
$q->expand('Trips/Stops');                 // path style (v3 + v4)

$q->expand('Trips', fn (ExpandBuilder $e) => $e
    ->select('Name', 'Budget')
    ->filter(fn (FilterBuilder $f) => $f->where('Status', 'eq', 'Completed'))
    ->orderBy('Name')
    ->orderByDesc('Budget')
    ->top(5)
);                                         // v4 nested options; throws on v3
$q->clearExpand();
```

### Ordering & paging

```php
$q->orderBy('LastName');                   // asc by default
$q->orderBy('LastName', SortDirection::Desc);
$q->orderByDesc('CreatedAt');
$q->clearOrderBy();

$q->top(50)->skip(100);
$q->skipToken('cursor-from-server');
$q->count();                               // $count=true (v4) or $inlinecount=allpages (v3)
```

### Other system options

```php
$q->search('foo bar');                     // v4 only — throws at render time on v3
$q->format('json');
$q->param('apikey', 'secret');             // arbitrary non-system param ($-prefixed keys rejected)
```

### Output

```php
$q->toArray();                             // ['$select' => '...', ...]
$q->toQueryString();                       // RFC 3986 encoded query string
(string) $q;                               // alias for toQueryString()
$q->applyTo($requestOrPendingRequest);
$q->clone();                               // independent fork (no shared state)
$q->fresh();                               // empty builder, same version
```

## Pagination

```php
use SimpleSquid\SaloonOData\Pagination\ODataPaginator;
use Saloon\PaginationPlugin\Contracts\Paginatable;

class GetPeople extends Request implements Paginatable { /* ... */ }

// Version is resolved from #[UsesODataVersion] attributes; pass explicitly only to override.
$paginator = new ODataPaginator($connector, new GetPeople);

foreach ($paginator->items() as $item) {
    // single record from any page
}
```

Reads spec-defined envelope keys only:

| Version | Next-link key  | Items key         |
|---------|----------------|-------------------|
| v4      | `@odata.nextLink` | `value`        |
| v3 JSON-Light | `__next`     | `value`        |
| v3 JSON-Verbose | `d.__next` | `d.results`    |

The paginator extracts only the `$skiptoken` from the next-link URL and applies it to the original request — it does not follow the full server-supplied URL.

Requests that need custom item extraction can implement Saloon's `MapPaginatedResponseItems` contract.

## Literal encoding

All `$filter` literal encoding goes through `Support\Literal::encode($value, $version)`. Supported types:

| PHP type | v4 output | v3 output |
|---|---|---|
| `null` | `null` | `null` |
| `bool` | `true`/`false` | `true`/`false` |
| `int` / `float` | `42` / `3.14` | `42` / `3.14` |
| `string` | `'value'` (single-quote escape: `''`) | `'value'` |
| `Guid` (via `Literal::guid()`) | bare `xxxxxxxx-...` | `guid'xxxxxxxx-...'` |
| `DateTimeInterface` | `2025-01-15T10:30:00Z` | `datetime'2025-01-15T10:30:00'` |
| `DateOnly` (via `Literal::dateOnly()` or `DateOnly::from()`) | `2025-01-15` | `datetime'2025-01-15'` |
| `BackedEnum` | encoded `value` | encoded `value` |
| `UnitEnum` | encoded case `name` | encoded case `name` |
| `array` | tuple `(a,b,c)` | tuple |

GUID detection is **opt-in** via `Literal::guid()` to prevent user-supplied strings that happen to look like GUIDs from silently changing semantics.

## Security

- Property names passed to `select()`, `where()`, `orderBy()`, `expand()`, etc. are validated against an OData identifier pattern. Anything containing spaces, quotes, parens, or other syntax characters throws `InvalidODataQueryException`.
- Literal values are version-correctly quote-escaped through `Support\Literal`.
- The paginator only extracts `$skiptoken` from server-supplied next-link URLs; it does not follow arbitrary URLs.
- `filterRaw()` and `FilterBuilder::raw()` are documented escape hatches. **Never pass untrusted input to either.**

## Testing

```bash
composer test
composer analyse
composer format
```

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).

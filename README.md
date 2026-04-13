# saloonphp-odata

A [Saloon](https://github.com/saloonphp/saloon) plugin providing a fluent, version-aware OData query builder and a server-driven paginator. Supports OData v3 and v4. Bring your own Connector — Saloon handles HTTP.

```php
$req = (new GetPeople)->odataQuery()
    ->select('FirstName', 'LastName')
    ->filter(fn (FilterBuilder $f) => $f
        ->where('Age', 'gt', 30)
        ->and()
        ->startsWith('LastName', 'O'))
    ->orderBy('LastName')
    ->top(10)
    ->count();

$connector->send($req);
// GET /People?$select=FirstName,LastName&$filter=Age gt 30 and startswith(LastName,'O')&$orderby=LastName asc&$top=10&$count=true
```

## Installation

```bash
composer require simplesquid/saloonphp-odata
```

For the paginator, also install Saloon's pagination plugin:

```bash
composer require saloonphp/pagination-plugin
```

Requires PHP 8.4+ and Saloon v3.

## Usage

### As a Request trait

```php
use Saloon\Enums\Method;
use Saloon\Http\Request;
use SimpleSquid\SaloonOData\Concerns\HasODataQuery;

class GetPeople extends Request
{
    use HasODataQuery;

    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/People';
    }
}
```

The trait exposes `$request->odataQuery()` which returns the underlying `ODataQueryBuilder`. The builder's params are merged into the request's query string immediately before send via Saloon middleware.

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
use SimpleSquid\SaloonOData\Attributes\ODataVersion;
use SimpleSquid\SaloonOData\Enums\ODataVersion as Version;

#[ODataVersion(Version::V3)]
#[ODataEntity('SalesInvoices')]
#[DefaultODataQuery(
    select: ['ID', 'InvoiceDate', 'AmountDC'],
    top: 50,
    count: true,
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

Defaults are applied on first access to `odataQuery()`. Runtime calls layer over them.

The version is resolved in this order:
1. Explicit `ODataQueryBuilder::make($version)` call.
2. `#[ODataVersion]` on the Request class (or any parent).
3. `#[ODataVersion]` on the Connector class (applied at request boot).
4. Default: v4.

> Filters and nested `$expand` are rendered lazily, so a connector-level version still applies cleanly even after the user has chained `->filter(...)` on the builder. The exception is `filterRaw()` — those strings are version-baked by the caller.

## Builder reference

### Selection

```php
$q->select('FirstName', 'LastName', 'Email');
```

### Filtering

```php
$q->filter(fn (FilterBuilder $f) => $f
    ->where('Age', 'gt', 30)              // comparison: eq, ne, gt, ge, lt, le, has*, in*
    ->and()                                // explicit logical join (defaults to `and`)
    ->or()                                 // switch the trailing join
    ->not()                                // negate the next clause
    ->group(fn (FilterBuilder $g) => ...)  // wrap in parentheses
    ->in('Status', ['A', 'B'])             // v4 only
    ->has('Roles', 'Admin')                // v4 only
    ->contains('Name', 'foo')              // becomes substringof('foo', Name) on v3
    ->startsWith('Name', 'A')
    ->endsWith('Name', 'Z')
    ->raw('year(Created) eq 2025')         // pre-encoded escape hatch
);

$q->filterRaw("Status eq 'Active'");       // bypass the closure entirely
```

Operators accept a `ComparisonOperator` enum or a string; strings are validated.

### Expansion

```php
$q->expand('Trips');                       // flat (works on v3 and v4)
$q->expand('Trips/Stops');                 // path style (v3 + v4)

$q->expand('Trips', fn (ExpandBuilder $e) => $e
    ->select('Name', 'Budget')
    ->filter(fn (FilterBuilder $f) => $f->where('Status', 'eq', 'Completed'))
    ->orderBy('Name')
    ->top(5)
);                                         // v4 nested options; throws on v3
```

### Ordering & paging

```php
$q->orderBy('LastName');                   // asc by default
$q->orderBy('LastName', SortDirection::Desc);
$q->orderByDesc('CreatedAt');

$q->top(50)->skip(100);
$q->skipToken('cursor-from-server');
$q->count();                               // $count=true (v4) or $inlinecount=allpages (v3)
```

### Other system options

```php
$q->search('foo bar');                     // v4 only — throws on v3
$q->format('json');
$q->param('apikey', 'secret');             // arbitrary non-system param
```

### Output

```php
$q->toArray();                             // ['$select' => '...', ...]
$q->toQueryString();                       // RFC 3986 encoded query string
(string) $q;                               // alias for toQueryString()
$q->applyTo($requestOrPendingRequest);
$q->clone();                               // independent fork
$q->fresh();                               // empty builder, same version
```

## Pagination

```php
use SimpleSquid\SaloonOData\Pagination\ODataPaginator;
use Saloon\PaginationPlugin\Contracts\Paginatable;

class GetPeople extends Request implements Paginatable { /* ... */ }

$paginator = new ODataPaginator($connector, new GetPeople, ODataVersion::V4);

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

Requests that need custom item extraction can implement Saloon's `MapPaginatedResponseItems` contract.

## Literal encoding

All `$filter` literal encoding goes through `Support\Literal::encode($value, $version)`. Supported types:

| PHP type | v4 output | v3 output |
|---|---|---|
| `null` | `null` | `null` |
| `bool` | `true`/`false` | `true`/`false` |
| `int` / `float` | `42` / `3.14` | `42` / `3.14` |
| `string` | `'value'` (single-quote escape: `''`) | `'value'` |
| `string` matching GUID | bare `xxxxxxxx-...` | `guid'xxxxxxxx-...'` |
| `DateTimeInterface` | `2025-01-15T10:30:00Z` | `datetime'2025-01-15T10:30:00'` |
| `DateOnly` (via `Literal::dateOnly($dt)`) | `2025-01-15` | `datetime'2025-01-15'` |
| `BackedEnum` | encoded `value` | encoded `value` |
| `array` | tuple `(a,b,c)` | tuple |

## Testing

```bash
composer test
composer analyse
composer format
```

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).

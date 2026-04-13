<?php

declare(strict_types=1);

namespace SimpleSquid\SaloonOData\Pagination;

use Saloon\Http\Connector;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Paginator;
use SimpleSquid\SaloonOData\Enums\ODataVersion;
use SimpleSquid\SaloonOData\Support\SkipToken;

/**
 * Walks server-driven pagination as defined by the OData specs.
 *
 * Reads the next-page URL from spec-defined response envelope keys:
 *   - v4: top-level `@odata.nextLink`
 *   - v3 JSON-Light: top-level `__next`
 *   - v3 JSON-Verbose: `d.__next`
 *
 * Items are pulled from the standard envelopes (`value` for v4 / v3 JSON-Light,
 * `d.results` / `d` for v3 JSON-Verbose).
 *
 * The paginator only parses spec-defined fields. Vendor extensions are out of
 * scope; a Request that needs to extract items differently can implement
 * Saloon's MapPaginatedResponseItems contract.
 */
final class ODataPaginator extends Paginator
{
    public function __construct(
        Connector $connector,
        Request $request,
        public readonly ODataVersion $version = ODataVersion::V4,
    ) {
        parent::__construct($connector, $request);
    }

    protected function applyPagination(Request $request): Request
    {
        if ($this->currentResponse === null) {
            return $request;
        }

        $token = self::extractToken($this->currentResponse, $this->version);

        if ($token !== null) {
            $request->query()->add('$skiptoken', $token);
        }

        return $request;
    }

    protected function isLastPage(Response $response): bool
    {
        return self::extractToken($response, $this->version) === null;
    }

    /**
     * @return array<int|string, mixed>
     */
    protected function getPageItems(Response $response, Request $request): array
    {
        $value = $response->json('value');

        if (is_array($value)) {
            return $value;
        }

        if ($this->version === ODataVersion::V3) {
            $verbose = $response->json('d.results');
            if (is_array($verbose)) {
                return $verbose;
            }

            $bare = $response->json('d');
            if (is_array($bare) && array_is_list($bare)) {
                return $bare;
            }
        }

        return [];
    }

    private static function extractToken(Response $response, ODataVersion $version): ?string
    {
        $next = $version === ODataVersion::V4
            ? $response->json('@odata.nextLink')
            : ($response->json('__next') ?? $response->json('d.__next'));

        return is_string($next) ? SkipToken::extract($next) : null;
    }
}

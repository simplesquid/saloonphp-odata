<?php

declare(strict_types=1);

namespace SimpleSquid\SaloonOData\Support;

/**
 * Pulls the `$skiptoken` value from a server-driven-paging URL.
 *
 * Accepts full URLs, relative URLs, or bare query strings.
 *
 * @see https://docs.oasis-open.org/odata/odata/v4.01/os/part1-protocol/odata-v4.01-os-part1-protocol.html#sec_ServerDrivenPaging
 */
final class SkipToken
{
    public static function extract(string $urlOrQueryString): ?string
    {
        $query = self::queryStringFrom($urlOrQueryString);

        if ($query === '') {
            return null;
        }

        parse_str($query, $params);

        $value = $params['$skiptoken'] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function queryStringFrom(string $input): string
    {
        // Already a bare query string?
        if (! str_contains($input, '?') && ! str_contains($input, '://')) {
            return ltrim($input, '?');
        }

        $query = parse_url($input, PHP_URL_QUERY);

        return is_string($query) ? $query : '';
    }
}

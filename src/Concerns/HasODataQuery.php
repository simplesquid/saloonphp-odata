<?php

declare(strict_types=1);

namespace SimpleSquid\SaloonOData\Concerns;

use Saloon\Enums\PipeOrder;
use Saloon\Http\PendingRequest;
use SimpleSquid\SaloonOData\Enums\ODataVersion;
use SimpleSquid\SaloonOData\ODataQueryBuilder;
use SimpleSquid\SaloonOData\Support\AttributeReader;

/**
 * Add OData query-building to a Saloon Request.
 *
 * On first access, lazily creates an {@see ODataQueryBuilder} using the
 * version from `#[UsesODataVersion]` on the Request (or any parent), falling
 * back to v4. Any `#[DefaultODataQuery]` on the Request is applied.
 *
 * At boot, if the Request didn't declare a version, the Connector's
 * `#[UsesODataVersion]` attribute is consulted and applied via `withVersion()`.
 * Filters and nested $expand are rendered lazily, so the late switch is
 * applied consistently to all chained calls. (Pre-encoded `filterRaw()`
 * fragments are version-baked by the caller.)
 *
 * Registers a request middleware that merges the builder's rendered params
 * into the PendingRequest's query bag immediately before send.
 */
trait HasODataQuery
{
    private ?ODataQueryBuilder $odataQuery = null;

    public function odataQuery(): ODataQueryBuilder
    {
        if ($this->odataQuery !== null) {
            return $this->odataQuery;
        }

        $version = AttributeReader::version($this) ?? ODataVersion::V4;
        $builder = ODataQueryBuilder::make($version);

        AttributeReader::applyDefaults($this, $builder);

        return $this->odataQuery = $builder;
    }

    /**
     * Read the `#[ODataEntity('...')]` declaration on the Request class.
     *
     * Useful inside `resolveEndpoint()` to keep the entity-set name in one place.
     */
    public function odataEntity(): ?string
    {
        return AttributeReader::entity($this);
    }

    public function bootHasODataQuery(PendingRequest $pendingRequest): void
    {
        // Skip if the user never touched the builder and no class-level
        // attributes drive defaults — there's nothing to merge.
        if (
            $this->odataQuery === null
            && AttributeReader::defaults($this) === null
            && AttributeReader::version($this) === null
            && AttributeReader::version($pendingRequest->getConnector()) === null
        ) {
            return;
        }

        $builder = $this->odataQuery();

        // Connector-level fallback: only when the Request itself is silent.
        if (AttributeReader::version($this) === null) {
            $connectorVersion = AttributeReader::version($pendingRequest->getConnector());
            if ($connectorVersion !== null) {
                $builder->withVersion($connectorVersion);
            }
        }

        $pendingRequest->middleware()->onRequest(
            static function (PendingRequest $request) use ($builder): void {
                $request->query()->merge($builder->toArray());
            },
            'odata-merge-query',
            PipeOrder::LAST,
        );
    }
}

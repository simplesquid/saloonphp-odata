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
 * On first access, lazily creates an {@see ODataQueryBuilder}. The version is
 * read from a `#[ODataVersion]` attribute on the Request class (or any of
 * its parents); falls back to OData v4. Any `#[DefaultODataQuery]` on the
 * Request is applied to the builder.
 *
 * Registers a request middleware that merges the builder's rendered params
 * into the PendingRequest's query bag immediately before the request is sent.
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
        $builder = $this->odataQuery();

        $pendingRequest->middleware()->onRequest(
            static function (PendingRequest $request) use ($builder): void {
                $request->query()->merge($builder->toArray());
            },
            'odata-merge-query',
            PipeOrder::LAST,
        );
    }
}

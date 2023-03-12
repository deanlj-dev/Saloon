<?php

declare(strict_types=1);

namespace Saloon\Traits\Connector;

use Saloon\Contracts\Response;
use Saloon\Helpers\LimitHelper;
use Saloon\Http\RateLimiting\Limit;
use Saloon\Contracts\PendingRequest;
use Saloon\Contracts\RateLimitStore;
use Saloon\Exceptions\RateLimitReachedException;

trait HasRateLimiting
{
    /**
     * Boot the has rate limiting trait
     *
     * @param \Saloon\Contracts\PendingRequest $pendingRequest
     * @return void
     * @throws \ReflectionException
     */
    public function bootHasRateLimiting(PendingRequest $pendingRequest): void
    {
        // Todo: Allow people to customise the name and not just the connector name

        // We'll now run the "setConnectorName" on each of the limits because
        // this will allow the limit classes to populate the ID properly.

        // We'll now have an array of the limits with IDs that can be generated.

        $pendingRequest->middleware()->onRequest(function (PendingRequest $pendingRequest) {
            // We'll check here if we have reached the rate limit - if we have
            // then we need to throw the limit exception

            if ($limit = $this->getExceededLimit()) {
                $this->throwLimitException($limit);
            }
        });

        $limits = LimitHelper::configureLimits($this->resolveLimits(), $this);
        $store = $this->resolveRateLimitStore();

        // Register the limit counter

        $pendingRequest->middleware()->onResponse(function (Response $response) use ($limits, $store) {
            $limitThatWasExceeded = null;

            foreach ($limits as $limit) {
                $limit = $store->hydrateLimit($limit);

                // We'll make sure our limits haven't been exceeded yet - if they haven't then
                // we will run the `checkForTooManyAttempts` method.

                // Todo: Consider renaming checkForTooManyAttempts

                if (is_null($limitThatWasExceeded)) {
                    $this->checkForTooManyAttempts($response, $limit);
                }

                if ($limit->hasExceeded()) {
                    $limitThatWasExceeded = $limit;
                }

                // Now we'll "hit" the limit which will increase the count

                $limit->hit();

                // Next, we'll commit the limit onto the store

                $store->commitLimit($limit);
            }

            if (isset($limitThatWasExceeded)) {
                $this->throwLimitException($limitThatWasExceeded);
            }
        });
    }

    /**
     * Resolve the limits for the rate limiter
     *
     * @return array<\Saloon\Http\RateLimiting\Limit>
     */
    abstract protected function resolveLimits(): array;

    /**
     * Resolve the rate limit store
     *
     * @return RateLimitStore
     */
    abstract protected function resolveRateLimitStore(): RateLimitStore;

    /**
     * Process the limit, can be extended
     *
     * @param \Saloon\Contracts\Response $response
     * @param \Saloon\Http\RateLimiting\Limit $limit
     * @return void
     */
    protected function checkForTooManyAttempts(Response $response, Limit $limit): void
    {
        if ($response->status() === 429) {
            $limit->exceeded();
        }
    }

    /**
     * Throw the limit exception
     *
     * @param \Saloon\Http\RateLimiting\Limit $limit
     * @return void
     * @throws \Saloon\Exceptions\RateLimitReachedException
     */
    protected function throwLimitException(Limit $limit): void
    {
        throw new RateLimitReachedException($limit);
    }

    /**
     * Get the first limit that has exceeded
     *
     * @param float|null $threshold
     * @return \Saloon\Http\RateLimiting\Limit|null
     * @throws \ReflectionException
     */
    public function getExceededLimit(?float $threshold = null): ?Limit
    {
        $limits = LimitHelper::configureLimits($this->resolveLimits(), $this);

        if (empty($limits)) {
            return null;
        }

        $store = $this->resolveRateLimitStore();

        foreach ($limits as $limit) {
            $limit = $store->hydrateLimit($limit);

            if ($limit->hasReachedLimit($threshold)) {
                return $limit;
            }
        }

        return null;
    }

    /**
     * Check if we have reached the rate limit
     *
     * @param float|null $threshold
     * @return bool
     * @throws \ReflectionException
     */
    public function hasReachedRateLimit(?float $threshold = null): bool
    {
        return $this->getExceededLimit($threshold) instanceof Limit;
    }
}

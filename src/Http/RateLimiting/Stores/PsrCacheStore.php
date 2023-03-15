<?php

namespace Saloon\Http\RateLimiting\Stores;

use Psr\SimpleCache\CacheInterface;
use Saloon\Contracts\RateLimitStore;
use Saloon\Http\RateLimiting\Limit;

class PsrCacheStore implements RateLimitStore
{
    /**
     * Constructor
     *
     * @param \Psr\SimpleCache\CacheInterface $cache
     */
    public function __construct(readonly protected CacheInterface $cache)
    {
        //
    }

    /**
     * Hydrate the properties on the limit (hits, timestamp etc)
     *
     * @param \Saloon\Http\RateLimiting\Limit $limit
     * @return \Saloon\Http\RateLimiting\Limit
     * @throws \JsonException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Saloon\Exceptions\LimitException
     */
    public function get(Limit $limit): Limit
    {
        $serializedLimitData = $this->cache->get($limit->getName());

        if (is_null($serializedLimitData)) {
            return $limit;
        }

        return $limit->unserializeStoreData($serializedLimitData);
    }

    /**
     * Commit the properties on the limit (hits, timestamp)
     *
     * @param \Saloon\Http\RateLimiting\Limit $limit
     * @return void
     * @throws \JsonException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function set(Limit $limit): void
    {
        $this->cache->set(
            key: $limit->getName(),
            value: $limit->serializeStoreData(),
            ttl: $limit->getRemainingSeconds(),
        );
    }
}

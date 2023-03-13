<?php

declare(strict_types=1);

namespace Saloon\Http\RateLimiting\Stores;

use Predis\Client;
use Saloon\Http\RateLimiting\Limit;
use Saloon\Contracts\RateLimitStore;

class RedisStore implements RateLimitStore
{
    /**
     * Constructor
     *
     * @param \Predis\Client $redis
     */
    public function __construct(protected Client $redis)
    {
        //
    }

    /**
     * Hydrate the properties on the limit (hits, timestamp etc)
     *
     * Todo: Consider changing the name
     *
     * @param \Saloon\Http\RateLimiting\Limit $limit
     * @return \Saloon\Http\RateLimiting\Limit
     * @throws \JsonException|\Saloon\Exceptions\LimitException
     */
    public function hydrateLimit(Limit $limit): Limit
    {
        $serializedLimitData = $this->redis->get($limit->getName());

        if (is_null($serializedLimitData)) {
            return $limit;
        }

        return $limit->unserializeStoreData($serializedLimitData);
    }

    /**
     * Commit the properties on the limit (hits, timestamp)
     *
     * Todo: Consider changing the name
     *
     * @param \Saloon\Http\RateLimiting\Limit $limit
     * @return void
     * @throws \JsonException
     */
    public function commitLimit(Limit $limit): void
    {
        $this->redis->setex(
            key: $limit->getName(),
            seconds: $limit->getRemainingSeconds(),
            value: $limit->serializeStoreData()
        );
    }
}

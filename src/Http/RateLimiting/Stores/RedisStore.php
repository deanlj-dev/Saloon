<?php

declare(strict_types=1);

namespace Saloon\Http\RateLimiting\Stores;

use Redis;
use Saloon\Http\RateLimiting\Limit;
use Saloon\Contracts\RateLimitStore;

class RedisStore implements RateLimitStore
{
    /**
     * Constructor
     *
     * @param \Redis $redis
     */
    public function __construct(protected Redis $redis)
    {
        //
    }

    /**
     * Hydrate the properties on the limit (hits, timestamp etc)
     *
     * @param \Saloon\Http\RateLimiting\Limit $limit
     * @return \Saloon\Http\RateLimiting\Limit
     * @throws \JsonException
     * @throws \RedisException
     * @throws \Saloon\Exceptions\LimitException
     */
    public function get(Limit $limit): Limit
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
     * @param \Saloon\Http\RateLimiting\Limit $limit
     * @return void
     * @throws \JsonException
     * @throws \RedisException
     */
    public function set(Limit $limit): void
    {
        $this->redis->setex(
            key: $limit->getName(),
            expire: $limit->getRemainingSeconds(),
            value: $limit->serializeStoreData()
        );
    }
}

<?php

declare(strict_types=1);

namespace Saloon\Helpers;

use Saloon\Contracts\Request;
use Saloon\Contracts\Connector;
use Saloon\Exceptions\LimitException;
use Saloon\Http\RateLimiting\Limit;

class LimitHelper
{
    /**
     * Hydrate the limits
     *
     * @param array<\Saloon\Http\RateLimiting\Limit> $limits
     * @param \Saloon\Contracts\Connector|\Saloon\Contracts\Request $connectorOrRequest
     * @return array<\Saloon\Http\RateLimiting\Limit>
     * @throws \ReflectionException
     * @throws \Saloon\Exceptions\LimitException
     */
    public static function configureLimits(array $limits, Connector|Request $connectorOrRequest): array
    {
        $limits = array_filter($limits, static fn (mixed $value) => $value instanceof Limit);

        if (empty($limits)) {
            return [];
        }

        $limits = Arr::mapWithKeys($limits, static function (Limit $limit, int|string $key) use ($connectorOrRequest) {
            return [$key => is_string($key) ? $limit->name($key) : $limit->setObjectName($connectorOrRequest)];
        });

        $limitNames = array_map(static fn(Limit $limit) => $limit->getName(), $limits);

        foreach (array_count_values($limitNames) as $name => $count) {
            if ($count === 1) {
                continue;
            }

            throw new LimitException(sprintf('Duplicate limit name "%s". Consider adding a custom name to the limit.', $name));
        }

        return array_values($limits);
    }
}

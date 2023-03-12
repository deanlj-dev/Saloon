<?php

declare(strict_types=1);

namespace Saloon\Exceptions;

use Saloon\Http\RateLimiting\Limit;

class RateLimitReachedException extends SaloonException
{
    /**
     * Constructor
     *
     * @param \Saloon\Http\RateLimiting\Limit $limit
     */
    public function __construct(readonly protected Limit $limit)
    {
        parent::__construct(sprintf('Request Rate Limit Reached (Name: %s)', $this->limit->getName()));
    }

    /**
     * Get the limit that was reached
     *
     * @return \Saloon\Http\RateLimiting\Limit
     */
    public function getLimit(): Limit
    {
        return $this->limit;
    }
}

<?php

declare(strict_types=1);

namespace Saloon\Tests\Fixtures\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Contracts\Response;

class AlwaysHasFailureRequest extends Request
{
    /**
     * Define the HTTP method.
     *
     * @var string
     */
    protected Method $method = Method::GET;

    /**
     * Define the endpoint for the request.
     *
     * @return string
     */
    public function resolveEndpoint(): string
    {
        return '/user';
    }

    /**
     * Determines if there is always a failure
     *
     * @param \Saloon\Contracts\Response $response
     * @return bool
     */
    public function shouldThrowRequestException(Response $response): bool
    {
        return true;
    }
}

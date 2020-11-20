<?php

namespace App\Routing\Specs;

use Amp\Http\Server\Request;
use Symfony\Component\HttpFoundation\Request as SfRequest;

trait SymfonyRequestTrait
{
    private function mapRequest(Request $request): SfRequest
    {
        return SfRequest::create(
            $request->getUri()->getPath() . '?' . $request->getUri()->getQuery(),
            $request->getMethod(),
            [],
            $request->getCookies(),
            [],
            $_SERVER + [
                'HTTP_HOST' => $request->getUri()->getHost() . ':' . $request->getUri()->getPort(),
            ]
        );
    }
}
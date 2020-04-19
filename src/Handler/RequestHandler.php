<?php

namespace App\Handler;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler as RequestHandlerInterface;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Promise;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use function Amp\call;

final class RequestHandler implements RequestHandlerInterface
{
    /** @var callable */
    private $callable;

    public function __construct(UrlMatcherInterface $urlMatcher, ?ProgressBar $status = null)
    {
        $this->callable = function (Request $request) use ($urlMatcher, $status) {
            if (null !== $status) {
                $status->advance();
            }

            try {
                $parameters = $urlMatcher->match($request->getUri()->getPath());
            } catch (ResourceNotFoundException $exception) {
                return new Response(Status::NOT_FOUND);
            }

            return ((new $parameters['_controller']())());
        };
    }

    public function handleRequest(Request $request): Promise
    {
        return call($this->callable, $request);
    }
}

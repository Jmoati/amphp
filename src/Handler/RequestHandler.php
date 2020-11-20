<?php

namespace App\Handler;

use Amp\Delayed;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler as RequestHandlerInterface;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Parallel\Worker\DefaultPool;
use Amp\Parallel\Worker\Worker;
use Amp\Promise;
use Amp\Success;
use App\Routing\AmphpRouting;
use App\Routing\StaticRouting;
use App\Routing\SymfonyRouting;
use App\Task\SymfonyTask;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Symfony\Component\HttpFoundation\Request as SfRequest;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use function Amp\call;

final class RequestHandler implements RequestHandlerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    
    /** @var callable */
    private $callable;
    
    public function __construct(
        iterable $routing
    ) {
        $this->callable = function (Request $request) use ($routing) {

            $this->logger->info(sprintf('[%s] %s', $request->getMethod(), $request->getUri()));
            
            foreach ($routing as $router) {
                if ($router->isSupported($request)) {
                    $response =  yield $router->handle($request);
                    $this->logger->info(sprintf('[%s] %d', get_class($router), $response->getStatus()));
        
                    return $response;
                }
            }
            
            return new Response(Status::NOT_FOUND);
        };
    }
    
    public function handleRequest(Request $request): Promise
    {
        return call($this->callable, $request);
    }
}

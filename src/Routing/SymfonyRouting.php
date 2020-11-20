<?php

namespace App\Routing;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Parallel\Worker\DefaultPool;
use Amp\Parallel\Worker\Pool;
use Amp\Promise;
use App\Routing\Specs\RoutingInterface;
use App\Routing\Specs\SymfonyRequestTrait;
use App\Task\SymfonyTask;
use function Amp\call;

final class SymfonyRouting implements RoutingInterface
{
    use SymfonyRequestTrait;
    
    private Pool $pool;
    
    public function __construct()
    {
        $this->pool = new DefaultPool();
    }
    
    public function isSupported(Request $request): bool
    {
        return true;
    }
    
    public function handle(Request $request): Promise
    {
        $task = new SymfonyTask($this->mapRequest($request));
        $taskPromise = $this->pool->enqueue($task);
        
        unset($task);
        
        return call(function ($taskPromise) {
            $taskResponse = yield $taskPromise;
            
            $response =  new Response(
                $taskResponse->getStatusCode(),
                $taskResponse->headers->all(),
                $taskResponse->getContent()
            );
            
            return $response;
        }, $taskPromise);
    }
    
    public static function getDefaultPriority(): int
    {
        return -100;
    }
}

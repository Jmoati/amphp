<?php

namespace App\Routing;

use Amp\Http\Server\Request;
use Amp\Promise;
use App\Routing\Specs\RoutingInterface;
use App\Routing\Specs\SymfonyRequestTrait;
use App\Server\Pool;

final class SymfonyRouting implements RoutingInterface
{
    use SymfonyRequestTrait;
    
    private Pool $pool;
    
    public function __construct()
    {
        $this->pool = new Pool();
    }
    
    public function isSupported(Request $request): bool
    {
        return true;
    }
    
    public function handle(Request $request): Promise
    {
        $request = $this->mapRequest($request);
        
        return $this->pool->handle($request);
    }
    
    public static function getDefaultPriority(): int
    {
        return -100;
    }
}

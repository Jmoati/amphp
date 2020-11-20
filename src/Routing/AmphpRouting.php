<?php

namespace App\Routing;

use Amp\Http\Server\Request;
use Amp\Promise;
use App\Routing\Specs\RoutingInterface;
use App\Routing\Specs\SymfonyRequestTrait;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Bundle\FrameworkBundle\Routing\AnnotatedRouteControllerLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Loader\AnnotationDirectoryLoader;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use function Amp\call;

final class AmphpRouting implements RoutingInterface
{
    use SymfonyRequestTrait;
    
    private UrlMatcher $router;
    
    public function __construct(string $projectDir)
    {
        $loader = new AnnotationDirectoryLoader(
            new FileLocator($projectDir.'/src/Action/'),
            new AnnotatedRouteControllerLoader(new AnnotationReader())
        );
    
        $routes = $loader->load($projectDir.'/src/Action/');
        $context = new RequestContext();
        $this->router = new UrlMatcher($routes, $context);
    }
    
    public function isSupported(Request $request): bool
    {
        try {
            $this->router->matchRequest($this->mapRequest($request));
        } catch (ResourceNotFoundException $exception) {
            return false;
        }
        
        return true;
    }
    
    public function handle(Request $request): Promise
    {
        return call(function() use ($request) {
           $parameters =  $this->router->matchRequest($this->mapRequest($request));
           
            return ((new $parameters['_controller']())($request));
        });
    }
    
    public static function getDefaultPriority(): int
    {
        return 0;
    }
    
    
}

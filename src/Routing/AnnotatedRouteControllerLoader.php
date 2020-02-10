<?php

namespace App\Routing;

use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Routing\Loader\AnnotationClassLoader;
use Symfony\Component\Routing\Route;

class AnnotatedRouteControllerLoader extends AnnotationClassLoader
{
    protected function configureRoute(Route $route, ReflectionClass $class, ReflectionMethod $method, $annot): void
    {
        if ('__invoke' === $method->getName()) {
            $route->setDefault('_controller', $class->getName());
        } else {
            $route->setDefault('_controller', $class->getName() . '::' . $method->getName());
        }
    }
    
    protected function getDefaultRouteName(ReflectionClass $class, ReflectionMethod $method): string
    {
        return preg_replace([
                                '/(bundle|controller)_/',
                                '/action(_\d+)?$/',
                                '/__/',
                            ], [
                                '_',
                                '\\1',
                                '_',
                            ], parent::getDefaultRouteName($class, $method));
    }
}

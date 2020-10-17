<?php

namespace App\Handler;

use Amp\ByteStream\ResourceInputStream;
use Amp\ByteStream\ResourceOutputStream;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler as RequestHandlerInterface;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Promise;
use App\Kernel;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use function Amp\call;

final class RequestHandler implements RequestHandlerInterface
{
    /** @var callable */
    private $callable;
    
    /** @var Kernel  */
    private $kernel;
    
    public function __construct(UrlMatcherInterface $urlMatcher, ?ProgressBar $status = null)
    {
        (new Dotenv())->bootEnv(dirname(__DIR__) . '/../.env');
        
        if ($_SERVER['APP_DEBUG']) {
            umask(0000);
            Debug::enable();
        }
        
        $this->kernel = new Kernel($_SERVER['APP_ENV'], (bool)$_SERVER['APP_DEBUG']);
        $this->kernel->boot();
        
        $this->callable = function (Request $request) use ($urlMatcher, $status) {
            if (null !== $status) {
                $status->advance();
            }
            
            $filename = realpath(__DIR__ . '/../../public' . $request->getUri()->getPath());
            if (file_exists($filename) && is_file($filename)) {
                try {
                    $resource = fopen(__DIR__ . '/../../public' . $request->getUri()->getPath(), 'r');
                    $stream = new ResourceInputStream($resource);
                    return new Response(\Symfony\Component\HttpFoundation\Response::HTTP_OK, [], $stream);
                } catch (\Exception $exception) {
                    dd($exception);
                }
            }
            
            try {
                $context = new RequestContext(
                    '/',
                    $request->getMethod(),
                    $request->getUri()->getHost(),
                );
                
                $urlMatcher->setContext($context);
                $parameters = $urlMatcher->match($request->getUri()->getPath());
            } catch (ResourceNotFoundException $exception) {
                $this->kernel->reboot(null);
                
                $sfRequest = \Symfony\Component\HttpFoundation\Request::create(
                    $request->getUri()->getPath() . '?' . $request->getUri()->getQuery(),
                    $request->getMethod(),
                    [],
                    $request->getCookies(),
                    [],
                    $_SERVER + [
                        'HTTP_HOST' => $request->getUri()->getHost() . ':' . $request->getUri()->getPort(),
                    ]
                );
                
                $response = $this->kernel->handle($sfRequest);
                $this->kernel->terminate($sfRequest, $response);
    
                return new Response(
                    $response->getStatusCode(),
                    $response->headers->all(),
                    $response->getContent()
                );
                
            } catch (MethodNotAllowedException $exception) {
                return new Response(Status::METHOD_NOT_ALLOWED);
            }
            
            return ((new $parameters['_controller']())($request));
        };
    }
    
    public function handleRequest(Request $request): Promise
    {
        return call($this->callable, $request);
    }
}

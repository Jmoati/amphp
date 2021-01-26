<?php

use Amp\Parallel\Sync\Channel;
use App\Kernel;
use App\Server\Response;
use App\Server\StreamedResponse;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request as SfRequest;
use Symfony\Component\HttpFoundation\Response as SfResponse;
use App\Server\Request;
use function Amp\call;

return function (Channel $channel): Generator
{
    register_shutdown_function(function () use ($channel) {
        $channel->send('Worker die ... Do you use die() or exit() ?');
    });
    
    (new Dotenv())->bootEnv(__DIR__ . '/../.env');
    
    if ($_SERVER['APP_DEBUG']) {
        umask(0000);
        Debug::enable();
    }
    
    $kernel = new Kernel($_SERVER['APP_ENV'], (bool)$_SERVER['APP_DEBUG']);
    
    while (true) {
        try {
            gc_collect_cycles();
            
            $sfRequest = new SfRequest();

            if ($request = yield $channel->receive()) {
                assert($request instanceof Request);

                $sfRequest = SfRequest::create(
                    $request->uri,
                    $request->method,
                    $request->parameters,
                    $request->cookies,
                    $request->files,
                    $request->server,
                    (null !== $request->contentPath) ? fopen($request->contentPath, 'r') ?: null : null
                );
            }
            
            $kernel->boot();
            
            try {
                $sfResponse = $kernel->handle($sfRequest);
            } catch (Exception $exception) {
                $sfResponse = new SfResponse((string)$exception);
            }
        } catch (Exception $exception) {
            $sfResponse = new SfResponse((string)$exception);
        }
    
        $kernel->terminate($sfRequest, $sfResponse);
        
        yield $channel->send(new Response(
            $sfResponse->getContent(),
            $sfResponse->headers,
            $sfResponse->getStatusCode()
        ));

        if ($sfResponse instanceof StreamedResponse) {
            yield call(function() use ($sfResponse, $channel) {
                $sfResponse->getCallback()($channel);
            });

            yield $channel->send(null);
        }
    }
};

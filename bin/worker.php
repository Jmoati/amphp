<?php

use Amp\Parallel\Sync\Channel;
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;

return function (Channel $channel): Generator {
    
    (new Dotenv())->bootEnv(__DIR__ . '/../.env');
    
    if ($_SERVER['APP_DEBUG']) {
        umask(0000);
        Debug::enable();
    }
    
    $kernel = new Kernel($_SERVER['APP_ENV'], (bool)$_SERVER['APP_DEBUG']);
    
    while (true) {
        gc_collect_cycles();
        
        $request = yield $channel->receive();
        $kernel->boot();
        
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        yield $channel->send($response->getContent());
        yield $channel->send(null);
        
    /*
    yield $channel->send($response->getContent());
    
*/
        /*
        yield $channel->send(null);
        */
        

//        $stream = fopen('http://test-debit.free.fr/1048576.rnd', 'r');
  
  /*      while (false !== ($data = stream_get_contents($stream, 1024*1024))) {
            yield $channel->send($data);
        }*/
        
    }
};

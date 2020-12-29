<?php


namespace App\Server;

use Amp\ByteStream\IteratorStream;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Producer;
use Amp\Promise;
use Amp\Parallel\Context;
use function Amp\call;

class Worker
{
    private Context\Context $context;
    private bool $idle = false;
    
    public function __construct()
    {
        $context = Context\create(__DIR__ . '/../../bin/worker.php');
        $this->context = $context;
    }
    
    public function isRunning(): bool
    {
        return true;
    }
    
    public function isIdle(): bool
    {
        return $this->idle;
    }
    
    public function shutdown(): Promise
    {
        return call(function () {
           $this->kill();
        });
    }
    
    public function kill()
    {
        $this->context->kill();
    }
    
    public function handle($request): Promise
    {
        $this->idle = false;
        
        return call(function () use ($request) {
            $context = $this->context;
            $idle = &$this->idle;
            
            if (!$context->isRunning()) {
                yield $context->start();
            }
        
            yield $context->send($request);
            
            return new Response(Status::OK, [], new IteratorStream(new Producer(function(callable $emit) use ($context, &$idle) {
                while ($data = yield $context->receive()) {
                    yield $emit($data);
                }
                
                $idle = true;
            })));
        });
    }
}

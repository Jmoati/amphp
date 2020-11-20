<?php

namespace App\Task;

use Amp\Parallel\Worker\Environment;
use Amp\Parallel\Worker\Task;
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\ResetInterface;

class SymfonyTask implements Task
{
    private Request $request;
    
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    
    public function run(Environment $environment): Response
    {
        (new Dotenv())->bootEnv(dirname(__DIR__) . '/../.env');
        
        if (!$environment->exists('kernel')) {
            if ($_SERVER['APP_DEBUG']) {
                umask(0000);
                Debug::enable();
            }
    
            $kernel = new Kernel($_SERVER['APP_ENV'], (bool)$_SERVER['APP_DEBUG']);
            $environment->set('kernel', $kernel);
        } else {
            $kernel = $environment->get('kernel');
        }
        
        $kernel->boot();
        
        $response = $kernel->handle($this->request);
        $kernel->terminate($this->request, $response);
    
        gc_collect_cycles();
    
        return $response;
    }
}
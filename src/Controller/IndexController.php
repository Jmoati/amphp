<?php

namespace App\Controller;

use Amp\Parallel\Sync\Channel;
use App\Server\StreamedResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class IndexController
{
    /**
     * @Route("/test")
     */
    public function __invoke(Request $request)
    {
        $response =  new StreamedResponse(function (Channel $channel) {
            $channel->send('Hello World'.PHP_EOL);
            sleep(5);
            $channel->send('Hello World'.PHP_EOL);
            sleep(5);
            $channel->send('Hello World'.PHP_EOL);
        });
        
        return $response;
    }
}

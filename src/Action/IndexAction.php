<?php

namespace App\Action;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Symfony\Component\Routing\Annotation\Route;

class IndexAction
{
    /**
     * @Route("/t")
     */
    public function __invoke(Request $request): Response
    {
        return new Response(Status::OK, [], __CLASS__ . ' : ' . __LINE__ .' : '.  $request->getUri());
    }
}

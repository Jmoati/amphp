<?php

require __DIR__ . '/vendor/autoload.php';

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Psr\Log\NullLogger;
use function Amp\Socket\listen;
use Amp\Loop;
use Amp\Http\Server\Server;

Loop::run(function() {
    $sockets = [
         listen("0.0.0.0:1337"),
         listen("[::]:1337"),
    ];

    $server = new Server($sockets, new CallableRequestHandler(function (Request $request) {
        return new Response(Status::OK, [
            "content-type" => "application/json; charset=utf-8",
        ],  json_encode((array)$request));
    }), new NullLogger());

    yield $server->start();

    Loop::onSignal(SIGINT, function (string $watcherId) use ($server) {
        Loop::cancel($watcherId);
        yield $server->stop();
    });
});
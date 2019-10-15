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

        $stream = $request->getBody();
        $stream->increaseSizeLimit(1 * 1024 * 1024 * 1024 * 1024);
        function convert($size)
        {
            $unit=array('b','kb','mb','gb','tb','pb');
            return(@round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i]);
        }

        while (($chunk = yield $stream->read()) !== null) {


            var_dump(convert(memory_get_usage(false))); // 123 kb
        }

        return new Response(Status::OK, [
            "content-type" => "application/json; charset=utf-8",
        ], 'hello');
    }), new NullLogger());

    yield $server->start();

    Loop::onSignal(SIGINT, function (string $watcherId) use ($server) {
        Loop::cancel($watcherId);
        yield $server->stop();
    });
});
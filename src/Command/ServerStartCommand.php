<?php

namespace App\Command;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Server;
use Amp\Http\Status;
use Amp\Loop;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function Amp\Socket\listen;

class ServerStartCommand extends Command
{
    protected static $defaultName = 'server:start';

    protected function configure()
    {
        $this
            ->setDescription("Start the Http server")
            ->addOption("port", "p", InputOption::VALUE_REQUIRED, "The port to be listen", 1337)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $status = new ProgressBar($output);
        $status->setFormat("Since: <info>%elapsed%</info> | Memory: <info>%memory%</info>  | Requests:  <info>%current%</info>");
        $status->start();

        Loop::run(function() use ($input, $status) {
            $sockets = [
                listen(sprintf("0.0.0.0:%d", $input->getOption("port"))),
                listen(sprintf("[::]:%d", $input->getOption("port"))),
            ];

            $server = new Server($sockets, new CallableRequestHandler(function (Request $request) use ($status) {
                $status->advance();

                $stream = $request->getBody();
                $stream->increaseSizeLimit(125000000000);

                while (($chunk = yield $stream->read()) !== null) {
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

            Loop::repeat(1000, function() use ($status) {
                $status->display();
            });
        });

        $status->finish();

        return 0;
    }
}
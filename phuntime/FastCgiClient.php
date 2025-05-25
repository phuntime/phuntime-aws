<?php

use Swoole\Coroutine\FastCGI\Client;
use function Swoole\Coroutine\run as swoole_co_run;

/**
 * Emits event to php-fpm.
 */
class FastCgiClient
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client(
            host: '127.0.0.1',
            port: 9901,
        );
    }

    public function handle(
        array $event,
    ): \Swoole\FastCGI\HttpResponse
    {
        /** @var ?\Swoole\FastCGI\HttpResponse $fcgiResponse */
        $fcgiResponse = null;

        swoole_co_run(function () use (&$fcgiResponse, $event) {
            $request = new \Swoole\FastCGI\HttpRequest();
            $request->withMethod($event['requestContext']['http']['method'])
                ->withDocumentRoot('/var/task')
                ->withScriptFilename('/var/task/index.php')
                ->withParam('SCRIPT_NAME', 'index.php');

            $fcgiResponse = $this
                ->client
                ->execute($request);
        });

        return $fcgiResponse;
    }
}
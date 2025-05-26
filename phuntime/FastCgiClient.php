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
                ->withScriptFilename('/var/task/index.php')
                ->withServerProtocol($event['requestContext']['http']['protocol'])
                ->withGatewayInterface('CGI/1.1')
                ->withScriptName('index.php')
                ->withQueryString($event['rawQueryString'])
                ->withParam('PATH_INFO', $event['rawPath'])
                ->withRequestUri(
                    $this->buildRequestUriParam(
                        $event['requestContext']['http']['path'],
                        $event['rawQueryString'],
                    )
                );

            if(isset($event['body']) && is_string($event['body'])) {
                $bodyResult = $this->parseBody(
                    $event['body'],
                    $event['isBase64Encoded']
                );


                $request->withBody(
                   $bodyResult
                );
            }

            foreach ($event['headers'] as $key => $value) {
                $request->withHeader($key, $value);

                if(strtolower($key) == 'content-type') {
                    $request->withContentType($value);
                }
            }

            $fcgiResponse = $this
                ->client
                ->execute($request);
        });

        return $fcgiResponse;
    }


    private function buildRequestUriParam(
        string $path,
        string $query,
    )
    {
        if(strlen($query) == 0) {
            return $path;
        }

        return sprintf('%s?%s', $path, $query);
    }

    private function parseBody(
        string $body,
        bool $isBase64Encoded,
    )
    {
        if($isBase64Encoded) {
            return base64_decode($body);
        }

        return $body;
    }
}
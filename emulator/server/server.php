<?php

use Swoole\Http\Request;
use Swoole\Http\Response;

$http = new Swoole\Http\Server('0.0.0.0', 9501);
$emulatorEndpoint = '127.0.0.1:8080';

/**
 * @return string
 * @throws \Random\RandomException
 * @see https://stackoverflow.com/questions/2040240/php-function-to-generate-v4-uuid
 */
function uuidv4(): string
{
    $data = random_bytes(16);

    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}


$http->on('Request', function (Request $request, Response $response) use ($emulatorEndpoint) {
    if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
        $response->status(404);
        $response->end();
        return;
    }

    $requestId = uuidv4();

    parse_str($request->server['query_string'] ?? '', $queryStringParams);
    $apiGwEvent = [
        'version' => '2.0',
        'isBase64Encoded' => false,
        'cookies' => $request->cookie ?? [], //@TODO verify what is returned when no cookies
        'headers' => $request->header,
        'rawQueryString' => $request->server['query_string'] ?? '',
        'queryStringParameters' => $queryStringParams,
        'requestContext' => [
            'domainName' => $request->header['host'] ?? '',
            'requestId' => $requestId,
            'http' => [
                'method' => $request->getMethod(),
                'path' => $request->server['path_info'],
                'protocol' => $request->server['server_protocol'],
            ]
        ]
    ];

    $curl = curl_init();
    curl_setopt(
        $curl,
        CURLOPT_URL,
        sprintf('%s/2015-03-31/functions/function/invocations', $emulatorEndpoint)
    );
    curl_setopt(
        $curl, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Content-Type: application/json']);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($apiGwEvent));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($curl);
    curl_close($curl);


    //$response->header('Content-Type', 'application/json; charset=utf-8');
    $response->end($result);
});

$http->start();
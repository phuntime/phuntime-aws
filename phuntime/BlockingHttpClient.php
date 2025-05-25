<?php

class BlockingHttpClient
{

    /**
     * Available $options:
     * - body - required when POST
     * - blocking - sets timeout to 0
     * - headers - array<string,string> with headers to pass
     *
     * @param string $method
     * @param string $url
     * @param array $options
     * @return array
     */
    public function request(string $method, string $url, array $options = []): array
    {
        $headersToSend = [];
        $blocking = $options['blocking'] ?? false;
        $ch = curl_init();

        $headers = [];
        curl_setopt($ch, CURLOPT_HEADERFUNCTION,
            static function ($curl, $header) use (&$headers) {
                $len = strlen($header);
                $explodedHeader = explode(':', $header, 2);
                if (count($explodedHeader) !== 2) {
                    return $len;
                }
                [$name, $value] = $explodedHeader;
                if (empty($value)) {
                    return $len;
                }

                $name = strtolower(trim($name));
                $headers[$name][] = trim($value);

                return $len;

            }
        );

        if ($method === 'POST') {
            $body = $options['body'] ?? '';
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            $headersToSend['Content-Length'] = strlen($body);
        }

        $processedHeaders = [];
        foreach ($headersToSend as $headerName => $headerValue) {
            $processedHeaders[] = sprintf('%s: %s', $headerName, $headerValue);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $processedHeaders);

        if($blocking) {
            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        }

        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'response' => $result,
            'headers' => $headers,
            'status' => $status
        ];
    }
}
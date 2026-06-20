<?php

declare(strict_types=1);

namespace RichardWooding\ArcGis\Http;

use RichardWooding\ArcGis\Exception\TransportException;

/**
 * CurlHttpClient is the default transport. It uses the built-in ext-curl
 * extension so the library carries no third-party Composer dependencies.
 */
final class CurlHttpClient implements HttpClient
{
    /**
     * @param float $timeout total request timeout in seconds (0 disables it)
     */
    public function __construct(
        private readonly float $timeout = 30.0,
    ) {
    }

    public function send(Request $request): Response
    {
        $ch = curl_init();
        if ($ch === false) {
            throw new TransportException('failed to initialise curl handle');
        }

        $headers = [];
        foreach ($request->headers as $name => $value) {
            $headers[] = $name . ': ' . $value;
        }

        curl_setopt($ch, CURLOPT_URL, $request->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request->method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, (int) round($this->timeout * 1000));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        if ($request->body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request->body);
        }

        $body = curl_exec($ch);
        if ($body === false) {
            $message = curl_error($ch);
            curl_close($ch);

            throw new TransportException('http request failed: ' . $message);
        }

        /** @var int $status */
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        return new Response($status, (string) $body);
    }
}

<?php

declare(strict_types=1);

namespace RichardWooding\ArcGis\Http;

use RichardWooding\ArcGis\Exception\TransportException;

/**
 * HttpClient is the transport abstraction the ArcGIS client sends requests
 * through. The default implementation is {@see CurlHttpClient}; supply your own
 * (for example, a wrapper around Guzzle or a PSR-18 client) when constructing
 * the client to control transport, retries, or proxies.
 */
interface HttpClient
{
    /**
     * send executes the request and returns the response.
     *
     * @throws TransportException on a transport-level failure (connection, DNS,
     *                            timeout)
     */
    public function send(Request $request): Response;
}

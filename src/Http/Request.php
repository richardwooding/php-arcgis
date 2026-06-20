<?php

declare(strict_types=1);

namespace RichardWooding\ArcGis\Http;

/**
 * Request is a minimal HTTP request description passed to an HttpClient.
 *
 * A GET request carries its parameters in the URL and a null body. A POST
 * request carries a form-encoded body and the matching Content-Type header.
 */
final class Request
{
    /**
     * @param non-empty-string      $method
     * @param non-empty-string      $url
     * @param array<string, string> $headers
     */
    public function __construct(
        public readonly string $method,
        public readonly string $url,
        public readonly ?string $body = null,
        public readonly array $headers = [],
    ) {
    }
}

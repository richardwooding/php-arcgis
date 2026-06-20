<?php

declare(strict_types=1);

namespace RichardWooding\ArcGis\Http;

/**
 * Response is a minimal HTTP response returned by an HttpClient.
 */
final class Response
{
    public function __construct(
        public readonly int $statusCode,
        public readonly string $body,
    ) {
    }
}

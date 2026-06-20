<?php

declare(strict_types=1);

namespace RichardWooding\ArcGis\Tests\Support;

use RichardWooding\ArcGis\Http\HttpClient;
use RichardWooding\ArcGis\Http\Request;
use RichardWooding\ArcGis\Http\Response;

/**
 * MockHttpClient is a test transport. It captures the most recent request for
 * assertion and delegates response generation to a handler closure — the
 * analogue of the Go suite's httptest newServer helper.
 */
final class MockHttpClient implements HttpClient
{
    public ?Request $lastRequest = null;
    public int $calls = 0;

    /** @var callable(Request): Response */
    private $handler;

    /**
     * @param callable(Request): Response $handler
     */
    public function __construct(callable $handler)
    {
        $this->handler = $handler;
    }

    /**
     * withBody returns a client that always responds with the given body and
     * status.
     */
    public static function withBody(string $body, int $status = 200): self
    {
        return new self(static fn (Request $r): Response => new Response($status, $body));
    }

    public function send(Request $request): Response
    {
        $this->lastRequest = $request;
        ++$this->calls;

        return ($this->handler)($request);
    }

    /**
     * lastParams returns the decoded query parameters of the most recent
     * request, whether they were sent in the URL (GET) or the body (POST).
     *
     * @return array<string, string>
     */
    public function lastParams(): array
    {
        if ($this->lastRequest === null) {
            return [];
        }

        $raw = $this->lastRequest->body;
        if ($raw === null) {
            $query = parse_url($this->lastRequest->url, PHP_URL_QUERY);
            $raw = is_string($query) ? $query : '';
        }

        parse_str($raw, $parsed);

        /** @var array<string, string> $result */
        $result = [];
        foreach ($parsed as $key => $value) {
            $result[(string) $key] = is_array($value) ? '' : (string) $value;
        }

        return $result;
    }

    public function lastParam(string $name): ?string
    {
        return $this->lastParams()[$name] ?? null;
    }
}

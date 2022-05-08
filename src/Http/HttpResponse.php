<?php

namespace RavenDB\Http;

class HttpResponse implements HttpResponseInterface
{
    private string $content = '';
    private int $statusCode = -1;
    private array $headers = [];

    public function __construct($content, $statusCode, $headers)
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = array_change_key_case($headers, CASE_LOWER);
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function containsHeader(string $key): bool
    {
        return array_key_exists(strtolower($key), $this->headers);
    }

    public function getFirstHeader(string $key): ?string
    {
        $key = strtolower($key);
        if (!$this->containsHeader($key)) {
            return null;
        }

        if (!count($this->headers[$key])) {
            return null;
        }

        return $this->headers[$key][0];
    }
}

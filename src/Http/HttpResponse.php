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
        $this->headers = $headers;
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
}

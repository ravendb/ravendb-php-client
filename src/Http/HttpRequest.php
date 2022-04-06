<?php

namespace RavenDB\Http;

class HttpRequest implements HttpRequestInterface
{
    public const GET = 'GET';
    public const PUT = 'PUT';
    public const POST = 'POST';
    public const DELETE = 'DELETE';

    private string $url;
    private string $method;
    private array $options;

    public function __construct(string $url, string $method = 'GET', array $options = [])
    {
        $this->url = $url;
        $this->method = $method;
        $this->options = $options;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function addHeader($parameter, $value): void
    {
        if (!array_key_exists('headers', $this->options)) {
            $this->options['headers'] = [];
        }

        $this->options['headers'][$parameter] = $value;
    }
}

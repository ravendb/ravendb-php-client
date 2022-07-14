<?php

namespace RavenDB\Http;

class HttpRequest implements HttpRequestInterface
{
    public const GET = 'GET';
    public const PUT = 'PUT';
    public const POST = 'POST';
    public const PATCH = 'PATCH';
    public const DELETE = 'DELETE';
    public const HEAD = 'HEAD';
    public const RESET = 'RESET';

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

    /**
     * @param string $parameter
     * @return mixed
     */
    public function getFirstHeader(string $parameter)
    {
        if (!array_key_exists('headers', $this->options)) {
            return null;
        }

        if (!array_key_exists($parameter, $this->options['headers'])) {
            return null;
        }

        return $this->options['headers'][$parameter];
    }
}

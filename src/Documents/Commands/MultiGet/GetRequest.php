<?php

namespace RavenDB\Documents\Commands\MultiGet;

use RavenDB\Type\ExtendedArrayObject;

class GetRequest
{
    private ?string $url = null;
    private ?ExtendedArrayObject $headers = null;
    private ?string $query = null;
    private ?string $method = null;
    private bool $canCacheAggressively = true;

    private ?ContentInterface $content = null;

    public function __construct()
    {
        $this->headers = new ExtendedArrayObject();
        $this->headers->setKeysCaseInsensitive(true);
    }

    /**
     * @return string Concatenated Url and Query.
     */
    public function getUrlAndQuery(): string
    {
        if ($this->query == null) {
            return $this->url;
        }

        if (str_starts_with($this->query, '?')) {
            return $this->url . $this->query;
        }

        return $this->url . "?" . $this->query;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function getHeaders(): ?ExtendedArrayObject
    {
        return $this->headers;
    }

    public function setHeaders(?ExtendedArrayObject $headers): void
    {
        $this->headers = $headers;
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function setQuery(?string $query): void
    {
        $this->query = $query;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(?string $method): void
    {
        $this->method = $method;
    }

    public function isCanCacheAggressively(): bool
    {
        return $this->canCacheAggressively;
    }

    public function setCanCacheAggressively(bool $canCacheAggressively): void
    {
        $this->canCacheAggressively = $canCacheAggressively;
    }

    public function getContent(): ?ContentInterface
    {
        return $this->content;
    }

    public function setContent(?ContentInterface $content): void
    {
        $this->content = $content;
    }
}

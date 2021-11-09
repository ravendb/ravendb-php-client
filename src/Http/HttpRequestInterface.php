<?php

namespace RavenDB\Http;

interface HttpRequestInterface
{
    public function getUrl(): string;
    public function setUrl(string $url): void;
    public function getMethod(): string;
    public function setMethod(string $method): void;
    public function getOptions(): array;
    public function setOptions(array $options): void;
}

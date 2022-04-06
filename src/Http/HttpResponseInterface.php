<?php

namespace RavenDB\Http;

interface HttpResponseInterface
{
    public function getContent(): string;
    public function getStatusCode(): int;
    public function getHeaders(): array;

    public function getFirstHeader(string $key): ?string;
}

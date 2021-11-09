<?php

namespace RavenDB\Http;

interface HttpClientInterface
{
    public function execute(HttpRequestInterface $request): HttpResponseInterface;
}

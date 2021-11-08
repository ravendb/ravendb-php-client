<?php

namespace RavenDB\Http\Adapter;

use Exception;
use RavenDB\Http\HttpClientInterface;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\HttpResponseInterface;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface as SymfonyHttpClientInterface;
use Symfony\Component\HttpClient\HttpClient as SymfonyHttpClient;

class HttpClient implements HttpClientInterface
{
    private SymfonyHttpClientInterface $client;

    public function __construct()
    {
        $this->client = SymfonyHttpClient::create();
    }

    /**
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    public function execute(HttpRequestInterface $request): HttpResponseInterface
    {
        $symfonyResponse =  $this->client->request($request->getMethod(), $request->getUrl(), $request->getOptions());

        return HttpResponseTransformer::fromHttpClientResponse($symfonyResponse);
    }
}

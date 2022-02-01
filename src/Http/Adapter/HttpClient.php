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

    // @todo: load this from environment variable
    // We added this proxy in order to send traffic through Fiddler, so we can spy requests
    private $proxy = 'http://127.0.0.1:8866';

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
        $options = $request->getOptions();

        if (!empty($this->proxy) && !key_exists('proxy', $options)) {
            $options['proxy'] = $this->proxy;
        }

        $symfonyResponse =  $this->client->request($request->getMethod(), $request->getUrl(), $options);

        return HttpResponseTransformer::fromHttpClientResponse($symfonyResponse);
    }
}

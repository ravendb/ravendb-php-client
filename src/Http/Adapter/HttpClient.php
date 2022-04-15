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

    private static string $proxy;

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

        if (!empty(self::$proxy) && !key_exists('proxy', $options)) {
            $options['proxy'] = self::$proxy;
        }

//        print_r($request);

        $serverResponse =  $this->client->request($request->getMethod(), $request->getUrl(), $options);

//        print_r($serverResponse->getContent());

        return HttpResponseTransformer::fromHttpClientResponse($serverResponse);
    }

    public static function useProxy(string $proxy): void
    {
        self::$proxy = $proxy;
    }
}

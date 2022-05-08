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

//        $url = 'http://127.0.0.1:8080/databases/test_db_1/admin/indexes?raft-request-id';
//
//        if (str_starts_with($request->getUrl(), $url)) {
//            echo $request->getUrl() . PHP_EOL;
//            print_r($options);
//        }

        $serverResponse =  $this->client->request($request->getMethod(), $request->getUrl(), $options);

        return HttpResponseTransformer::fromHttpClientResponse($serverResponse);
    }

    public static function useProxy(string $proxy): void
    {
        self::$proxy = $proxy;
    }
}

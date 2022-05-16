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

    private array $clientOptions;

    public function __construct(array $options = [])
    {
        $this->clientOptions = $options;
        $this->client = SymfonyHttpClient::create();
    }

    /**
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    public function execute(HttpRequestInterface $request): HttpResponseInterface
    {
        $options = array_merge($this->clientOptions, $request->getOptions());

        if (!empty(self::$proxy) && !key_exists('proxy', $options)) {
            $options['proxy'] = self::$proxy;
        }


//        $needle = 'http://127.0.0.1:8080/databases/test_db_1/queries?queryHash=';
//        if (str_starts_with($request->getUrl(), $needle)) {
//            echo PHP_EOL . PHP_EOL;
//            echo 'URL: ' . $request->getUrl() . PHP_EOL;
//            print_r($options);
//        }
        $serverResponse =  $this->client->request($request->getMethod(), $request->getUrl(), $options);

//        if (str_starts_with($request->getUrl(), $needle)) {
//            print_r($serverResponse->getContent());
//        }

        return HttpResponseTransformer::fromHttpClientResponse($serverResponse);
    }

    public static function useProxy(string $proxy): void
    {
        self::$proxy = $proxy;
    }
}

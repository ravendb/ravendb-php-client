<?php

namespace RavenDB\Http\Adapter;

use Exception;
use RavenDB\Extensions\JsonExtensions;
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

        $needle = '';
//        $needle = 'http://127.0.0.1:8080/databases/test_db_1/indexes/has-changed';
//        $needle = 'http://127.0.0.1:8080/databases/test_db_1/docs?&pageSize=25&startsWith=A';
//        $needle = 'http://127.0.0.1:8080/databases/test_db_1/cmpxchg?&startsWith=test';
//        $needle = 'http://127.0.0.1:8080/databases/test_db_1/bulk_docs?&disableAtomicDocumentWrites=false&raft-request-id=';
//        $needle = 'http://127.0.0.1:8080/databases/test_db_1/queries?queryHash=';
//        echo $request->getUrl() . PHP_EOL;
//        if (str_starts_with($request->getUrl(), $needle)) {
//            echo PHP_EOL . PHP_EOL;
//            echo 'URL: ' . $request->getUrl() . PHP_EOL;
//            echo 'Method: ' . $request->getMethod() . PHP_EOL;
//            print_r($options);
//            echo JsonExtensions::getDefaultEntityMapper()->serialize($options, 'json') . PHP_EOL;
//        }
        $serverResponse =  $this->client->request($request->getMethod(), $request->getUrl(), $options);

//        if (str_starts_with($request->getUrl(), $needle)) {
//            print_r($serverResponse->getContent(false));
//            echo PHP_EOL;
//        }

        return HttpResponseTransformer::fromHttpClientResponse($serverResponse);
    }

    public static function useProxy(string $proxy): void
    {
        self::$proxy = $proxy;
    }
}

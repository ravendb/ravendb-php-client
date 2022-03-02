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


        // @todo: Remove this it is security vulnerability
        $options['verify_peer'] = false;
        $options['verify_host'] = false;

//        $options['capath'] = __DIR__;// . DIRECTORY_SEPARATOR . 'server.pfx';
//        $options['cafile'] = __DIR__ . DIRECTORY_SEPARATOR . 'localhost.crt';
//        $options['cafile'] = __DIR__ . DIRECTORY_SEPARATOR . 'ca.crt';

//        $options['local_cert'] =  '/Users/aleksandar/Projects/ravendb/certs/server.pem';
//        $options['local_pk'] = __DIR__ . DIRECTORY_SEPARATOR . 'server.pem';
//        $options['passphrase'] = '';


        $symfonyResponse =  $this->client->request($request->getMethod(), $request->getUrl(), $options);

        if (false && $symfonyResponse->getStatusCode() > 399) {
            echo PHP_EOL;
            echo '>>> ' . $symfonyResponse->getStatusCode() . ' <<<' . PHP_EOL;
            print_r($request->getUrl());
//            print_r($options);
            print_r($symfonyResponse->getInfo());
        }

        return HttpResponseTransformer::fromHttpClientResponse($symfonyResponse);
    }

    public static function useProxy(string $proxy): void
    {

        self::$proxy = $proxy;
    }
}

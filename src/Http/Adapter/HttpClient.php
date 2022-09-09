<?php

namespace RavenDB\Http\Adapter;

use Exception;
use InvalidArgumentException;
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

        $this->updateRequestBody($options);

        $log = false;
        if ($log) {
            echo PHP_EOL . '  --- ' . PHP_EOL . PHP_EOL;
            print_r($request->getUrl());
            echo PHP_EOL;
            print_r($options);
            echo PHP_EOL;
        }

        $serverResponse = $this->client->request($request->getMethod(), $request->getUrl(), $options);

        if ($log) {
            print_r($serverResponse->getContent()) . PHP_EOL;
        }

        return HttpResponseTransformer::fromHttpClientResponse($serverResponse);
    }

    // Symfony client use json_encode to serialize request data,
    // we need to have more control over serialization,
    // so we are going to serialize it throughout our serializer.
    private function updateRequestBody(array &$options): void
    {
        if (array_key_exists('json', $options)) {
            if (array_key_exists('body', $options)) {
                throw new InvalidArgumentException('Define either the "json" or the "body" option, setting both is not supported.');
            }

            $json = JsonExtensions::getDefaultEntityMapper()->serialize($options['json'], 'json', [
                "empty_array_as_object" => true,
                "json_encode_options" => JSON_UNESCAPED_UNICODE
            ]);

            unset($options['json']);

            $options['body'] = $json;
            $options['headers']['Content-type'] = 'application/json; charset=utf8';
        }
    }

    public static function useProxy(string $proxy): void
    {
        self::$proxy = $proxy;
    }
}

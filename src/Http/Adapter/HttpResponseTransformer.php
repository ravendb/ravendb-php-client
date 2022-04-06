<?php

namespace RavenDB\Http\Adapter;

use RavenDB\Http\HttpResponse;
use RavenDB\Http\HttpResponseInterface;

use Symfony\Contracts\HttpClient\ResponseInterface;

class HttpResponseTransformer
{
    /**
     * @throws \Exception
     */
    public static function fromHttpClientResponse(ResponseInterface $symfonyResponse): HttpResponseInterface
    {
        try {
            $content = $symfonyResponse->getContent(false);
            $statusCode = $symfonyResponse->getStatusCode();
            $headers = $symfonyResponse->getHeaders(false);
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage());
        }

        return new HttpResponse(
            $content,
            $statusCode,
            $headers
        );
    }
}

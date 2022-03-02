<?php

namespace RavenDB\Utils;

use RavenDB\Constants\Headers;
use RavenDB\Http\HttpResponse;

class HttpUtils
{

    public static function getEtagHeader(HttpResponse $response): ?string
    {
        $headers = $response->getHeaders();
        if (!array_key_exists(Headers::ETAG, $headers)) {
            return null;
        }

        return $headers[Headers::ETAG];
    }
}

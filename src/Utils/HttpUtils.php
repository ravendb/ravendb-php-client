<?php

namespace RavenDB\Utils;

use RavenDB\Constants\Headers;
use RavenDB\Http\HttpResponse;

class HttpUtils
{

    public static function getEtagHeader(HttpResponse $response): ?string
    {
        if (!$response->containsHeader(Headers::ETAG)) {
            return null;
        }

        return $response->getFirstHeader(Headers::ETAG);
    }
}

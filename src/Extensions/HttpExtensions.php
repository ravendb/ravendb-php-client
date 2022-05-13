<?php

namespace RavenDB\Extensions;

use RavenDB\Constants\Headers;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Http\HttpResponse;
use RavenDB\Utils\StringUtils;

class HttpExtensions
{
    public static function getRequiredEtagHeader(?HttpResponse $response): ?string
    {
        if ($response->containsHeader(Headers::ETAG)) {
            $firstHeader = $response->getFirstHeader(Headers::ETAG);
            $value = $firstHeader;
            if (StringUtils::isNotEmpty($value)) {
                return self::etagHeaderToChangeVector($value);
            } else {
                throw new IllegalStateException("Response did't had an ETag header");
            }
        } else {
            throw new IllegalStateException("Response did't had an ETag header");
        }
    }

    public static function getEtagHeader(?HttpResponse $response): ?string
    {
        if ($response->containsHeader(Headers::ETAG)) {
            $firstHeader =  $response->getFirstHeader(Headers::ETAG);
            if ($firstHeader !== null) {
                return self::etagHeaderToChangeVector($firstHeader);
            }
        }

        return null;
    }

    private static function etagHeaderToChangeVector(?string $responseHeader): ?string
    {
        if (StringUtils::isEmpty($responseHeader)) {
            throw new IllegalStateException("Response did't had an ETag header");
        }

        if (str_starts_with("\"", $responseHeader)) {
            return substr($responseHeader, 1, strlen($responseHeader) - 2);
        }

        return $responseHeader;
    }

//    public static String getEtagHeader(Map<String, String> headers) {
//        if (headers.containsKey(Constants.Headers.ETAG)) {
//            return etagHeaderToChangeVector(headers.get(Constants.Headers.ETAG));
//        } else {
//            return null;
//        }
//    }

//    public static Boolean getBooleanHeader(CloseableHttpResponse response, String header) {
//        if (response.containsHeader(header)) {
//            Header firstHeader = response.getFirstHeader(header);
//            String value = firstHeader.getValue();
//            return Boolean.valueOf(value);
//        } else {
//            return null;
//        }
//    }
}

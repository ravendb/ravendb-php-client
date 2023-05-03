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

    public static function getEtagHeaderFromArray(array $headers): ?string
    {
        if (array_key_exists(Headers::ETAG, $headers)) {
            return self::etagHeaderToChangeVector($headers[Headers::ETAG]);
        } else {
            return null;
        }
    }

    public static function getBooleanHeader(?HttpResponse $response, ?string $header): ?bool
    {
        if ($response->containsHeader($header)) {
            $firstHeader =  $response->getFirstHeader($header);
            if ($firstHeader !== null) {
                return boolval($firstHeader);
            }
        }

        return null;
    }
}

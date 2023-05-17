<?php

namespace RavenDB\Utils;

class UrlUtils
{
    public static function escapeDataString(string $s): string
    {
        return urlencode($s);
    }

    public static function urlHasQuery(string $url): bool
    {
        return parse_url($url, PHP_URL_QUERY);
    }

    public static function appendQuery(string $url, $parameter, $value): string
    {
        $parsed_url = parse_url($url);

        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host     = $parsed_url['host'] ?? '';
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user     = $parsed_url['user'] ?? '';
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = $parsed_url['path'] ?? '';
        $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] . '&' : '?';
        $query    .= $parameter . '=' . $value;
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';

        return "$scheme$user$pass$host$port$path$query$fragment";
    }
}

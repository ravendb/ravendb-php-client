<?php

namespace RavenDB\Utils;

class UrlEncoder
{
    public static function encode(string $value): string
    {
        return urlencode($value);
    }

}

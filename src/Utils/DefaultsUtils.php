<?php

namespace RavenDB\Utils;

class DefaultsUtils
{
    public static function defaultValue(string $className)
    {
        return new $className();
    }
}

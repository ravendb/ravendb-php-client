<?php

namespace RavenDB\Utils;

class LoggerFactory
{
    public static function getLogger(string $className): Logger
    {
        return new Logger($className);
    }
}

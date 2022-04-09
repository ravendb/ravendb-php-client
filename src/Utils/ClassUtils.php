<?php

namespace RavenDB\Utils;

use ReflectionClass;

class ClassUtils
{
    public static function getSimpleClassName(string $className): string
    {
        try {
            $reflect = new ReflectionClass($className);
            return $reflect->getShortName();
        } catch (\Exception $exception) {
            return '';
        }
    }
}

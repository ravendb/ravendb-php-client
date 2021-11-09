<?php

namespace RavenDB\Utils;

use Doctrine\Inflector\InflectorFactory;

class StringUtils
{
    public static function isBlank(string $value): bool
    {
        return !$value;
    }

    public static function isNotBlank(string $value): bool
    {
        return !self::isBlank($value);
    }

    public static function startsWith(string $needle, string $value): bool
    {
        // @todo: implement this metod
        return false;
    }

    public static function endsWith(string $needle, string $value): bool
    {
        // @todo: implement this metod
        return false;
    }

    public static function overflow32(int $v): int
    {
        $v = $v % 4294967296;
        if ($v > 2147483647) {
            return $v - 4294967296;
        } elseif ($v < -2147483648) {
            return $v + 4294967296;
        } else {
            return $v;
        }
    }

    public static function hashCode(string $s): int
    {
        $h = 0;
        $len = strlen($s);
        for ($i = 0; $i < $len; $i++) {
            $h = self::overflow32(31 * $h + ord($s[$i]));
        }

        return $h;
    }

    public static function pluralize(string $string): string
    {
        $inflector = InflectorFactory::create()->build();
        return $inflector->pluralize($string);
    }
}

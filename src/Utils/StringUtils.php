<?php

namespace RavenDB\Utils;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;

class StringUtils
{
    // @todo: Implement this as explained here: https://stackoverflow.com/questions/23419087/stringutils-isblank-vs-string-isempty
    public static function isBlank(?string $value): bool
    {
        return !$value;
    }

    public static function isNotBlank(?string $value): bool
    {
        return !self::isBlank($value);
    }

    public static function isEmpty(?string $value): bool
    {
        return empty($value);
    }

    public static function isNotEmpty(?string $value): bool
    {
        return !self::isEmpty($value);
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

    private static ?Inflector $inflector = null;

    public static function pluralize(string $string): string
    {
        if (self::$inflector == null) {
            self::$inflector = InflectorFactory::create()->build();
        }

        return self::$inflector->pluralize($string);
    }

    public static function capitalize(string $propertyName): string
    {
        return ucwords($propertyName);
    }

    public static function isNullOrWhitespace(?string $value): bool
    {
        if ($value == null) {
            return true;
        }

        return strlen(trim($value)) == 0;
    }

    public static function stripStart(?string $string, ?string $stripChars = null): ?string
    {
        if (empty($string)) {
            return $string;
        }

        if (empty($stripChars)) {
            return ltrim($string);
        }

        return ltrim ($string, $stripChars);
    }

    public static function stripEnd(?string $string, ?string $stripChars): ?string
    {
        if (empty($string)) {
            return $string;
        }

        if (empty($stripChars)) {
            return rtrim($string);
        }

        return rtrim($string, $stripChars);
    }

    public static function repeat(string $string, int $times): string
    {
        return str_repeat($string, $times);
    }

    public static function equalsIgnoreCase(?string $first, ?string $second): bool
    {
        if ($first == null || $second == null) {
            return false;
        }

        return strcasecmp($first, $second) == 0;
    }
}

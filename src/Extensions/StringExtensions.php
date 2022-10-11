<?php

namespace RavenDB\Extensions;

use RavenDB\Utils\StringBuilder;
use RavenDB\Utils\StringEscapeUtils;
use RavenDB\Utils\StringUtils;

class StringExtensions
{
//  public static String toWebSocketPath(String path) {
//        return path.replaceAll("http://", "ws://")
//                .replaceAll("https://", "wss://");
//    }
//
//    public static boolean isIdentifier(String token) {
//        return isIdentifier(token, 0, token.length());
//    }
//
//    public static boolean isIdentifier(String token, int start, int length) {
//        if (length == 0 || length > 256) {
//            return false;
//        }
//
//        if (!Character.isLetter(token.charAt(start)) && token.charAt(start) != '_') {
//            return false;
//        }
//
//        for (int i = 1; i < length; i++) {
//            if (!Character.isLetterOrDigit(token.charAt(start + i)) && token.charAt(start + i) != '_') {
//                return false;
//            }
//        }
//
//        return true;
//    }

    public static function escapeString(StringBuilder $builder, string $value): void
    {
        if (StringUtils::isBlank($value)) {
            return;
        }

        self::escapeStringInternal($builder, $value);
    }

    private static function escapeStringInternal(StringBuilder $builder, string $value): void
    {
        $escaped = StringEscapeUtils::escapeEcmaScript($value);
        $builder->append($escaped);
    }

    public static function stringFromEntity(?object $entity): ?string
    {
        if ($entity != null && method_exists($entity, '__toString')) {
            return $entity->__toString();
        }

        return null;
    }
}

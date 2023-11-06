<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Utils\StringBuilder;


abstract class QueryToken
{
    public abstract function writeTo(StringBuilder &$writer): void;

    private static array $RQL_KEYWORDS = [
        "as",
        "select",
        "where",
        "load",
        "group",
        "order",
        "include"
    ];

    public static function writeField(StringBuilder &$writer, string $field): void
    {
        $keyWord = self::isKeyword($field);

        if ($keyWord) {
            $writer->append("'" . $field . "'");
            return ;
        }

        $writer->append($field);
    }

    public static function isKeyword(string $field): bool
    {
        return in_array($field, self::$RQL_KEYWORDS);
    }
}

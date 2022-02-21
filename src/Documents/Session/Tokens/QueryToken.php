<?php

namespace RavenDB\Documents\Session\Tokens;

// !status: DONE
abstract class QueryToken
{
    public abstract function writeTo(): string;

    private static array $RQL_KEYWORDS = [
        "as",
        "select",
        "where",
        "load",
        "group",
        "order",
        "include"
    ];

    protected function writeField(string $field): string
    {
        $keyWord = in_array($field, self::$RQL_KEYWORDS);

        if ($keyWord) {
            return "'" . $field . "'";
        }

        return $field;
    }
}

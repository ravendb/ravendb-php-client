<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Utils\StringBuilder;

// !status: DONE
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

    protected function writeField(StringBuilder &$writer, string $field): void
    {
        $keyWord = in_array($field, self::$RQL_KEYWORDS);

        if ($keyWord) {
            $writer->append("'" . $field . "'");
            return ;
        }

        $writer->append($field);
    }
}

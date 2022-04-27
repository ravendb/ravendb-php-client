<?php

namespace RavenDB\Utils;

class StringEscapeUtils
{

    public static function escapeEcmaScript(string $value): string
    {
        // @todo: validate that this is what we want to perform with this method
        return addslashes($value);
    }
}

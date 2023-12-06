<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Utils\StringBuilder;

class DistinctToken extends QueryToken
{
    private function __construct()
    {
    }

    private static ?DistinctToken $INSTANCE = null;

    public function writeTo(StringBuilder &$writer): void
    {
        $writer->append("distinct");
    }

    public static function getInstance(): DistinctToken
    {
        if (DistinctToken::$INSTANCE == null) {
            DistinctToken::$INSTANCE = new DistinctToken();
        }

        return DistinctToken::$INSTANCE;
    }
}


<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Utils\StringBuilder;

class NegateToken extends QueryToken
{
    private function __construct()
    {
    }

    private static ?NegateToken $INSTANCE = null;

    public static function instance(): NegateToken
    {
        if (self::$INSTANCE == null) {
            self::$INSTANCE = new NegateToken();
        }

        return self::$INSTANCE;
    }

    public function writeTo(StringBuilder &$writer): void
    {
        $writer->append("not");
    }
}

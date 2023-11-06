<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Utils\StringBuilder;

class TrueToken extends QueryToken
{
    private function __construct()
    {
    }

    private static ?TrueToken $INSTANCE = null;

    public static function instance(): TrueToken
    {
        if (self::$INSTANCE == null) {
            self::$INSTANCE = new TrueToken();
        }

        return self::$INSTANCE;
    }

    public function writeTo(StringBuilder &$writer): void
    {
        $writer->append("true");
    }
}

<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Utils\StringBuilder;

class IntersectMarkerToken extends QueryToken
{
    private static ?IntersectMarkerToken $INSTANCE = null;

    private function __construct()
    {

    }

    public static function getInstance(): IntersectMarkerToken
    {
        if (self::$INSTANCE == null) {
            self::$INSTANCE = new IntersectMarkerToken();
        }
        return self::$INSTANCE;
    }

    public function writeTo(StringBuilder &$writer): void
    {
        $writer->append(',');
    }
}

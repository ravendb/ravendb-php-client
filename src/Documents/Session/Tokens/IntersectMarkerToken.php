<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Utils\StringBuilder;

// !status: DONE
class IntersectMarkerToken extends QueryToken
{
    public static ?IntersectMarkerToken $INSTANCE = null;

    public function __construct()
    {
        if (self::$INSTANCE == null) {
            self::$INSTANCE = new IntersectMarkerToken();
        }
    }

    public function writeTo(StringBuilder &$writer): void
    {
        $writer->append(',');
    }
}

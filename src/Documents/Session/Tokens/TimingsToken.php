<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Utils\StringBuilder;

class TimingsToken
{

    public static ?TimingsToken $instance = null;

    public static function instance(): TimingsToken
    {
        if (self::$instance == null) {
            self::$instance = new TimingsToken();
        }

        return self::$instance;
    }
    private function __construct() {}

    public function writeTo(StringBuilder $writer): void
    {
        $writer->append("timings()");
    }
}

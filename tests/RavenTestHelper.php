<?php

namespace tests\RavenDB;

use DateTime;

class RavenTestHelper
{
    public static function utcToday(): DateTime
    {
        $today = new DateTime();
        $today->setTime(0,0);
        return $today;
    }
}

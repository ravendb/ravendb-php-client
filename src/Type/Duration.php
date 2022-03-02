<?php

namespace RavenDB\Type;

use DateInterval;

class Duration
{
    private DateInterval $interval;

    public function __construct()
    {

    }

    public static function ofMillis(int $millis): Duration
    {
        // @todo: implement this
        return new Duration();
    }

    public static function between(\DateTime $lastServerUpdate, \DateTime $param): Duration
    {
        // @todo: implement this
        return new Duration();
    }
}

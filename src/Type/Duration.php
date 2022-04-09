<?php

namespace RavenDB\Type;

use DateInterval;
use RavenDB\Utils\HashUtils;

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

    public function hashCode(): int
    {
        return HashUtils::hashCode($this->interval->format(\DateTimeInterface::RFC3339_EXTENDED));
    }

    public function equals(Duration &$that): bool
    {

        // @todo: implement this method
        return true;
    }
}

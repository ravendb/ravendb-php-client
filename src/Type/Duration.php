<?php

namespace RavenDB\Type;

use DateInterval;
use DateTime;
use RavenDB\Utils\HashUtils;

class Duration
{
    private float $intervalInSeconds = 0;

    public static function ofHours(int $hours): Duration
    {
        $duration = new Duration();
        $duration->intervalInSeconds = $hours * 3600;
        return $duration;
    }

    public static function ofMillis(int $millis): Duration
    {
        $duration = new Duration();
        $duration->intervalInSeconds = $millis / 1000;
        return $duration;
    }

    public static function between(\DateTime $start, \DateTime $end): Duration
    {
        $duration = new Duration();
//        $duration->interval = $start->diff($end);
        return $duration;
    }

    public function hashCode(): int
    {
        $period = new DateInterval('PT'. $this->intervalInSeconds .'S');
        return HashUtils::hashCode($period->format(\DateTimeInterface::RFC3339_EXTENDED));
    }

    public function equals(Duration &$that): bool
    {

        // @todo: implement this method
        return true;
    }

    public function toMillis(): int
    {
        return (int)$this->intervalInSeconds * 1000;
    }

    public function getSeconds(): int
    {
        return (int)$this->intervalInSeconds;
    }
}

<?php

namespace RavenDB\Type;

use RavenDB\Utils\HashUtils;

class Duration
{
    const MILLISECONDS_IN_HOUR = 60*60*1000;
    const MILLISECONDS_IN_MINUTE = 60*1000;
    const MILLISECONDS_IN_SECOND = 1000;

    private int $intervalInMilliSeconds = 0;

    public static function ofHours(int $hours): Duration
    {
        $duration = new Duration();
        $duration->intervalInMilliSeconds = $hours * self::MILLISECONDS_IN_HOUR;
        return $duration;
    }

    public static function ofMillis(int $millis): Duration
    {
        $duration = new Duration();
        $duration->intervalInMilliSeconds = $millis;
        return $duration;
    }

    public static function between(\DateTime $start, \DateTime $end): Duration
    {
        $duration = new Duration();
        $duration->intervalInMilliSeconds = ($end->getTimestamp() - $start->getTimestamp()) * 1000;
        return $duration;
    }

    public static function ofMinutes(int $minutes): Duration
    {
        $duration = new Duration();
        $duration->intervalInMilliSeconds = $minutes * self::MILLISECONDS_IN_MINUTE;
        return $duration;
    }

    public static function ofSeconds(int $seconds): Duration
    {
        $duration = new Duration();
        $duration->intervalInMilliSeconds = $seconds * self::MILLISECONDS_IN_SECOND;
        return $duration;
    }

    public function format(): string
    {
        $hours = intval(floor($this->intervalInMilliSeconds / self::MILLISECONDS_IN_HOUR));
        $minutes = intval(floor(($this->intervalInMilliSeconds % self::MILLISECONDS_IN_HOUR) / self::MILLISECONDS_IN_MINUTE));
        $seconds = intval(floor(($this->intervalInMilliSeconds % self::MILLISECONDS_IN_MINUTE) / self::MILLISECONDS_IN_SECOND));

        $f = $this->intervalInMilliSeconds % self::MILLISECONDS_IN_SECOND;

        return "$hours:$minutes:$seconds.$f";
    }

    public function hashCode(): int
    {
        return HashUtils::hashCode($this->format());
    }

    public function equals(Duration &$that): bool
    {
        return $that->intervalInMilliSeconds == $this->intervalInMilliSeconds;
    }

    public function toMillis(): int
    {
        return $this->intervalInMilliSeconds;
    }

    public function toMicros(): int
    {
        return $this->intervalInMilliSeconds * 1000;
    }

    public function toNanos(): int
    {
        return $this->intervalInMilliSeconds * 1000000;
    }

    public function getSeconds(): float
    {
        return (float)$this->intervalInMilliSeconds / 1000;
    }

    public function compareTo(Duration $getDuration): int
    {
        // @todo: implement this
        return -1;
    }
}

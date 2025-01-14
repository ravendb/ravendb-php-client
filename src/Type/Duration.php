<?php

namespace RavenDB\Type;

use DateInterval;
use Exception;
use RavenDB\Utils\HashUtils;

class Duration
{
    const MILLISECONDS_IN_YEAR = 365*24*60*60*1000;

    const MILLISECONDS_IN_DAY = 24*60*60*1000;
    const MILLISECONDS_IN_HOUR = 60*60*1000;
    const MILLISECONDS_IN_MINUTE = 60*1000;
    const MILLISECONDS_IN_SECOND = 1000;

    private int $intervalInMilliSeconds = 0;

    public static function ofYears(int $years): Duration
    {
        $duration = new Duration();
        $duration->intervalInMilliSeconds = $years * self::MILLISECONDS_IN_YEAR;
        return $duration;
    }

    public static function ofDays(int $days): Duration
    {
        $duration = new Duration();
        $duration->intervalInMilliSeconds = $days * self::MILLISECONDS_IN_DAY;
        return $duration;
    }

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

    public static function zero(): Duration
    {
        return Duration::ofMillis(0);
    }

    public function format(): string
    {
        $days = intval(floor($this->intervalInMilliSeconds / self::MILLISECONDS_IN_DAY));
        $hours = intval(floor(($this->intervalInMilliSeconds % self::MILLISECONDS_IN_DAY) / self::MILLISECONDS_IN_HOUR));
        $minutes = intval(floor(($this->intervalInMilliSeconds % self::MILLISECONDS_IN_HOUR) / self::MILLISECONDS_IN_MINUTE));
        $seconds = intval(floor(($this->intervalInMilliSeconds % self::MILLISECONDS_IN_MINUTE) / self::MILLISECONDS_IN_SECOND));

        $duration = '';
        if ($days > 0) {
            $duration = $days . '.';
        }

        $duration .= sprintf("%02d:%02d:%02d", $hours,$minutes,$seconds);

        $f = intval(floor($this->intervalInMilliSeconds % self::MILLISECONDS_IN_SECOND));

        if ($f) {
            $duration .= '.' . sprintf("%03d", $f) . '0000';
        }

        return $duration;
    }

    public function toString(): string
    {
        return $this->format();
    }

    public static function fromString(string $duration): Duration
    {
        // Duration format is: days.hours:minutes:seconds.millis0000
        // and days and millis are optional

        $data = explode(':', $duration);

        $millis = 0;
        if (strpos($data[0], '.')) { // we have days value before hours
            $dh = explode('.', $data[0]);
            $millis += intval($dh[0]) * self::MILLISECONDS_IN_DAY;
            $millis += intval($dh[1]) * self::MILLISECONDS_IN_HOUR;
        } else {
            $millis += intval($data[0]) * self::MILLISECONDS_IN_HOUR;
        }

        $millis += intval($data[1]) * self::MILLISECONDS_IN_MINUTE;

        if (strpos($data[2], '.')) {
            $sm = explode('.', $data[2]);
            $millis += intval($sm[0]) * self::MILLISECONDS_IN_SECOND;
            $millis += intval(($sm[1] / 10000) * 1000); // cut the last four digits (zeros)
        } else {
            $millis += intval($data[2]) * self::MILLISECONDS_IN_SECOND;
        }

        return Duration::ofMillis($millis);
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

    public function toDateInterval(): DateInterval
    {
        // Convert milliseconds to total seconds and remaining microseconds
        $seconds = floor($this->intervalInMilliSeconds / 1000);
        $microseconds = ($this->intervalInMilliSeconds % 1000) * 1000;

        // Create a DateInterval from the seconds
        $interval = new DateInterval('PT' . $seconds . 'S');

        // Add microseconds if needed
        if ($microseconds > 0) {
            $interval->f = $microseconds / 1e6; // Set the fraction of a second
        }

        return $interval;
    }
}

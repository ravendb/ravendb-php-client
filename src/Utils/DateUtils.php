<?php

namespace RavenDB\Utils;

use DateInterval;
use DateTime;
use DateTimeInterface;

class DateUtils
{

    public static function addHours(DateTimeInterface $dateTime, int $hours): DateTimeInterface
    {
        $newDateTime = new DateTime();
        $newDateTime->setTimestamp($dateTime->getTimestamp());
        $interval = new DateInterval('PT' . abs($hours) . 'H');
        if ($hours < 0) {
            $interval->invert = 1;
        }
        $newDateTime->add($interval);
        return $newDateTime;
    }

    public static function addMinutes(DateTimeInterface $dateTime, int $minutes): DateTimeInterface
    {
        $newDateTime = new DateTime();
        $newDateTime->setTimestamp($dateTime->getTimestamp());
        $interval = new DateInterval('PT' . abs($minutes) . 'H');
        if ($minutes < 0) {
            $interval->invert = 1;
        }
        $newDateTime->add($interval);
        return $newDateTime;
    }

    public static function addDays(DateTimeInterface $dateTime, int $days): DateTimeInterface
    {
        $newDateTime = new DateTime();
        $newDateTime->setTimestamp($dateTime->getTimestamp());
        $interval = new DateInterval('P' . abs($days) . 'D');
        if ($days < 0) {
            $interval->invert = 1;
        }
        $newDateTime->add($interval);
        return $newDateTime;
    }

    public static function unixEpochStart(): DateTimeInterface
    {
        $d = new DateTime();
        $d->setTimestamp(0);
        return $d;
    }

    public static function setYears(DateTime $date, int $year): DateTime
    {
        $date->setDate($year, $date->format('m'), $date->format('d'));
        return $date;
    }
}

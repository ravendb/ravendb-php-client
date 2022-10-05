<?php

namespace RavenDB\Utils;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

class DateUtils
{

    /**
     * @param DateTime|DateTimeImmutable $dateTime
     * @param int $hours
     * @return DateTimeInterface
     * @throws \Exception
     */
    public static function addHours(DateTimeInterface $dateTime, int $hours): DateTimeInterface
    {
        $interval = new DateInterval('PT' . abs($hours) . 'H');
        if ($hours < 0) {
            $interval->invert = 1;
        }

        $newDateTime = clone $dateTime;
        return $newDateTime->add($interval);
    }

    /**
     * @param DateTime|DateTimeImmutable $dateTime
     * @param int $minutes
     * @return DateTimeInterface
     * @throws \Exception
     */
    public static function addMinutes(DateTimeInterface $dateTime, int $minutes): DateTimeInterface
    {
        $interval = new DateInterval('PT' . abs($minutes) . 'H');
        if ($minutes < 0) {
            $interval->invert = 1;
        }

        $newDateTime = clone $dateTime;
        return $newDateTime->add($interval);
    }

    /**
     * @param DateTime|DateTimeImmutable $dateTime
     * @param int $days
     *
     * @return DateTimeInterface
     *
     * @throws \Exception
     */
    public static function addDays(DateTimeInterface $dateTime, int $days): DateTimeInterface
    {
        $interval = new DateInterval('P' . abs($days) . 'D');
        if ($days < 0) {
            $interval->invert = 1;
        }

        $newDateTime = clone $dateTime;
        return $newDateTime->add($interval);
    }

    public static function unixEpochStart(): DateTimeInterface
    {
        $d = self::now();
        $d->setTimestamp(0);
        return $d;
    }

    public static function setYears(DateTime $date, int $year): DateTime
    {
        $date->setDate($year, $date->format('m'), $date->format('d'));
        return $date;
    }

    /**
     * @throws \Exception
     */
    public static function now(): DateTime
    {
        return new DateTime('now', new DateTimeZone('Z'));
    }
}

<?php

namespace RavenDB\Utils;

use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;

class DateUtils
{
    /**
     * @param DateTime|DateTimeImmutable $dateTime
     * @param int $years
     *
     * @return DateTime|DateTimeImmutable
     *
     * @throws Exception
     */
    public static function addYears(DateTime|DateTimeImmutable $dateTime, int $years): DateTime|DateTimeImmutable
    {
        $interval = new DateInterval('P' . abs($years) . 'Y');
        if ($years < 0) {
            $interval->invert = 1;
        }

        $newDateTime = clone $dateTime;
        return $newDateTime->add($interval);
    }

    /**
     * @param DateTime|DateTimeImmutable $dateTime
     * @param int $months
     *
     * @return DateTime|DateTimeImmutable
     *
     * @throws Exception
     */
    public static function addMonths(DateTime|DateTimeImmutable $dateTime, int $months): DateTime|DateTimeImmutable
    {
        $interval = new DateInterval('P' . abs($months) . 'M');
        if ($months < 0) {
            $interval->invert = 1;
        }

        $newDateTime = clone $dateTime;
        return $newDateTime->add($interval);
    }

    /**
     * @param DateTime|DateTimeImmutable $dateTime
     * @param int $days
     *
     * @return DateTime|DateTimeImmutable
     *
     * @throws Exception
     */
    public static function addDays(DateTime|DateTimeImmutable $dateTime, int $days): DateTime|DateTimeImmutable
    {
        $interval = new DateInterval('P' . abs($days) . 'D');
        if ($days < 0) {
            $interval->invert = 1;
        }

        $newDateTime = clone $dateTime;
        return $newDateTime->add($interval);
    }

    /**
     * @param DateTime|DateTimeImmutable $dateTime
     * @param int $hours
     * @return DateTime
     * @throws Exception
     */
    public static function addHours(DateTime|DateTimeImmutable $dateTime, int $hours): DateTime
    {
        $interval = new DateInterval('PT' . abs($hours) . 'H');
        if ($hours < 0) {
            $interval->invert = 1;
        }

        $newDateTime = clone $dateTime;
        return $newDateTime->add($interval);
    }

    /**
     * @param DateTime $dateTime
     * @param int $minutes
     * @return DateTime
     * @throws Exception
     */
    public static function addMinutes(DateTime $dateTime, int $minutes): DateTime
    {
        $interval = new DateInterval('PT' . abs($minutes) . 'M');
        if ($minutes < 0) {
            $interval->invert = 1;
        }

        $newDateTime = clone $dateTime;
        return $newDateTime->add($interval);
    }

    /**
     * @param DateTime $dateTime
     * @param int $seconds
     * @return DateTime
     * @throws Exception
     */
    public static function addSeconds(DateTime $dateTime, int $seconds): DateTime
    {
        $interval = new DateInterval('PT' . abs($seconds) . 'S');
        if ($seconds < 0) {
            $interval->invert = 1;
        }

        $newDateTime = clone $dateTime;
        return $newDateTime->add($interval);
    }

    /**
     * @param DateTime $dateTime
     * @param int $milliseconds
     * @return DateTime
     * @throws Exception
     */
    public static function addMilliseconds(DateTime $dateTime, int $milliseconds): DateTime
    {
        $newDateTime = clone $dateTime;
        $sign = $milliseconds < 0 ? '-' : '+';
        $newDateTime->modify($sign . ' '. abs($milliseconds) . ' milliseconds');
        return $newDateTime;
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
     * @throws Exception
     */
    public static function today(): DateTime
    {
        $today = self::now();
        $today->setTime(0,0);
        return $today;
    }

    /**
     * @throws Exception
     */
    public static function now(): DateTime
    {
        return new DateTime('now', new DateTimeZone('Z'));
    }

    public static function truncateDayOfMonth(DateTime $date): DateTime
    {
        $newDate = clone $date;
        $newDate->setTime(0, 0,0,0);

        return $newDate;
    }

    public static function ensureMilliseconds(DateTimeInterface $date): DateTimeInterface
    {
        /** @var DateTime $new */
        $new = clone $date;

        $hour = intval($new->format('H'));
        $minute = intval($new->format('i'));
        $second =intval($new->format('s'));
        $microsecond = intval($new->format('u'));
        $microsecond -= $microsecond % 1000;
        $new->setTime($hour, $minute, $second, $microsecond);

        return $new;
    }

    public static function intervalToMilliseconds(DateInterval $interval): int
    {
        $seconds = date_create('@0')->add($interval)->getTimestamp();

        return $seconds * 1000 + intval($interval->f * 1000) ;
    }


}

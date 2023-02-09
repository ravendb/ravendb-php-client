<?php

namespace RavenDB\Primitives;

use RavenDB\Constants\PhpClient;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use Symfony\Component\Serializer\Annotation\SerializedName;

class TimeValue
{
    private const SECONDS_PER_DAY = 86_400;
    private const SECONDS_IN_28_DAYS = 28 * self::SECONDS_PER_DAY;   // lower-bound of seconds in month
    private const SECONDS_IN_31_DAYS = 31 * self::SECONDS_PER_DAY;   // upper-bound of seconds in month
    private const SECONDS_IN_365_DAYS = 365 * self::SECONDS_PER_DAY; // lower-bound of seconds in a year
    private const SECONDS_IN_366_DAYS = 366 * self::SECONDS_PER_DAY; // upper-bound of seconds in a year

    #[SerializedName("Value")]
    private ?int $value = null;

    #[SerializedName("Unit")]
    private ?TimeValueUnit $unit = null;

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function setValue(?int $value): void
    {
        $this->value = $value;
    }

    public function getUnit(): ?TimeValueUnit
    {
        return $this->unit;
    }

    public function setUnit(?TimeValueUnit $unit): void
    {
        $this->unit = $unit;
    }

    private function __construct(int $value, TimeValueUnit $unit)
    {
        $this->value = $value;
        $this->unit = $unit;
    }

    public static function zero(): TimeValue
    {
        return new TimeValue(0, TimeValueUnit::none());
    }

    public static function minValue(): TimeValue
    {
        return new TimeValue(PhpClient::INT_MIN_VALUE, TimeValueUnit::none());
    }

    public static function maxValue(): TimeValue
    {
        return new TimeValue(PhpClient::INT_MAX_VALUE, TimeValueUnit::none());
    }

    public static function ofSeconds(int $seconds): TimeValue
    {
        return new TimeValue($seconds, TimeValueUnit::second());
    }

    public static function ofMinutes(int $minutes): TimeValue
    {
        return new TimeValue($minutes * 60, TimeValueUnit::second());
    }

    public static function ofHours(int $hours): TimeValue
    {
        return new TimeValue($hours * 3600, TimeValueUnit::second());
    }

    public static function ofDays(int $days): TimeValue
    {
        return new TimeValue($days * self::SECONDS_PER_DAY, TimeValueUnit::second());
    }

    public static function ofMonths(int $months): TimeValue
    {
        return new TimeValue($months, TimeValueUnit::month());
    }

    public static function ofYears(int $years): TimeValue
    {
        return new TimeValue(12 * $years, TimeValueUnit::month());
    }


    private function append(int $value, string $singular): string
    {
        if ($value <= 0) {
            return '';
        }

        $str = $value;
        $str .= " ";
        $str .= $singular;

        if ($value > 1) {
            $str .= "s ";// lucky me, no special rules here
        }

        return $str;
    }

    public function __toString(): string
    {
        return $this->toString();
    }


    public function toString(): string
    {
        if ($this->getValue() == PHP_INT_MAX) {
            return "MaxValue";
        }
        if ($this->getValue()== PHP_INT_MIN) {
            return "MinValue";
        }

        if ($this->getValue() == 0) {
            return "Zero";
        }

        if ($this->getUnit()->isNone()) {
            return "Unknown time unit";
        }

        $str = '';

        switch ($this->unit->getValue()) {
            case TimeValueUnit::SECOND:
                $remainingSeconds = $this->value;

                if ($remainingSeconds > TimeValue::SECONDS_PER_DAY) {
                    $days = $this->value / TimeValue::SECONDS_PER_DAY;
                    $str .= $this->append( $days, "day");
                    $remainingSeconds -= $days * TimeValue::SECONDS_PER_DAY;
                }

                if ($remainingSeconds > 3_600) {
                    $hours = $remainingSeconds / 3_600;
                    $str .= $this->append($hours, "hour");
                    $remainingSeconds -= $hours * 3_600;
                }

                if ($remainingSeconds > 60) {
                    $minutes = $remainingSeconds / 60;
                    $str .= $this->append( $minutes, "minute");
                    $remainingSeconds -= $minutes * 60;
                }

                if ($remainingSeconds > 0) {
                    $str .= $this->append($remainingSeconds, "second");
                }
                break;
            case TimeValueUnit::MONTH:
                if ($this->value >= 12) {
                    $str .= $this->append($this->value / 12, "year");
                }
                if ($this->value % 12 > 0) {
                    $str .= $this->append($this->value % 12, "month");
                }
                break;

            default:
                throw new IllegalArgumentException("Not supported unit: " . $this->unit);
        }

        return trim($str);
    }

    private function assertSeconds(): void {
        if (!$this->getUnit()->isSecond()) {
            throw new IllegalArgumentException("The value must be seconds");
        }
    }

    private static function assertValidUnit(TimeValueUnit $unit): void {
        if ($unit->isMonth() || $unit->isSecond()) {
            return;
        }

        throw new IllegalArgumentException("Invalid time unit: " . $unit);
    }

    private static function assertSameUnits(TimeValue $a , TimeValue $b): void
    {
        if ($a->getUnit()->getValue() !== $b->getUnit()->getValue()) {
            throw new IllegalStateException("Unit isn't the same " . $a->getUnit() . " != " . $b->getUnit());
        }
    }

    public function compareTo(TimeValue $other): int
    {
        if ($this->value == 0 || $other->getValue() == 0) {
            return $this->value - $other->getValue();
        }

        $resultRef = 0;
        if (self::isSpecialCompare($this, $other, $resultRef)) {
            return $resultRef;
        }

        if ($this->getUnit() === $other->getUnit()) {
            return self::trimCompareResult($this->getValue() - $other->getValue());
        }

        $myBounds = self::getBoundsInSeconds($this);
        $otherBounds = self::getBoundsInSeconds($other);

        if ($otherBounds[1] < $myBounds[0]) {
            return 1;
        }

        if ($otherBounds[0] > $myBounds[1]) {
            return -1;
        }

        throw new IllegalStateException("Unable to compare " . $this . " with " . $other . ", since a month might have different number of days.");
    }

    private static function getBoundsInSeconds(TimeValue $time): array
    {
        switch ($time->unit->getValue()) {
            case TimeValueUnit::SECOND:
                return [ $time->value, $time->value ];
            case TimeValueUnit::MONTH:
                $years = $time->value / 12;
                $upperBound = $years * self::SECONDS_IN_366_DAYS;
                $lowerBound = $years * self::SECONDS_IN_365_DAYS;

                $remainingMonths = $time->value % 12;
                $upperBound += $remainingMonths * self::SECONDS_IN_31_DAYS;
                $lowerBound += $remainingMonths * self::SECONDS_IN_28_DAYS;

                return [ $lowerBound, $upperBound ];
            default:
                throw new IllegalArgumentException("Not supported time value unit: " . $time->unit->getValue());
        }
    }

    private static function isSpecialCompare(TimeValue $current, TimeValue $other, int & $resultRef): bool
    {
        $resultRef = 0;

        if (self::isMax($current)) {
            $resultRef = self::isMax($other) ? 0 : 1;
            return true;
        }

        if (self::isMax($other)) {
            $resultRef = self::isMax($current) ? 0 : -1;
            return true;
        }

        if (self::isMin($current)) {
            $resultRef = self::isMin($other) ? 0 : -1;
            return true;
        }

        if (self::isMin($other)) {
            $resultRef = self::isMin($current) ? 0 : 1;
            return true;
        }

        return false;
    }

    private static function isMax(TimeValue $time): bool
    {
        return $time->getUnit()->isNone() && $time->getValue() == PHP_INT_MAX;
    }

    private static function isMin(TimeValue $time): bool
    {
        return $time->getUnit()->isNone() && $time->getValue() == PHP_INT_MIN;
    }

    private static function trimCompareResult(int $result): int {
        if ($result > PHP_INT_MAX) {
            return PHP_INT_MAX;
        }

        if ($result < PHP_INT_MIN) {
            return PHP_INT_MIN;
        }

        return intval($result);
    }

    public function equals(?object $o): bool {
        if ($this == $o) return true;
        if (($o == null) || (get_class($o) != get_class($this)) ) return false;

        /** @var TimeValue $other */
        $other = $o;
        return $this->compareTo($other) == 0;
    }
}

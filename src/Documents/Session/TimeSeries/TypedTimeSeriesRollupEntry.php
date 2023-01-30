<?php

namespace RavenDB\Documents\Session\TimeSeries;

use DateTime;
use RavenDB\Exceptions\RavenException;

class TypedTimeSeriesRollupEntry
{
    private ?string $className = null;

    private ?DateTime $timestamp = null;
    private ?string $tag = null;
    private bool $rollup = false;

    private mixed $first = null;
    private mixed $last = null;
    private mixed $max = null;
    private mixed $min = null;
    private mixed $sum = null;
    private mixed $count = null;

    private mixed $average = null;

    public function __construct(?string $className, ?DateTime $timestamp)
    {
        $this->className = $className;
        $this->rollup = true;
        $this->timestamp = $timestamp;
    }

    public function getTimestamp(): ?DateTime
    {
        return $this->timestamp;
    }

    public function setTimestamp(?DateTime $timestamp): void
    {
        $this->timestamp = $timestamp;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function setTag(?string $tag): void
    {
        $this->tag = $tag;
    }

    public function isRollup(): bool
    {
        return $this->rollup;
    }

    public function setRollup(bool $rollup): void
    {
        $this->rollup = $rollup;
    }

    private function createInstance(): mixed
    {
        try {
            $c = $this->className;
            return new $c();
        } catch (\Throwable $e) {
            throw new RavenException("Unable to create instance of class: " . $this->className, $e);
        }
    }

    public function getMax(): mixed
    {
        if ($this->max == null) {
            $this->max = self::createInstance();
        }

        return $this->max;
    }

    public function getMin(): mixed
    {
        if ($this->min == null) {
            $this->min = self::createInstance();
        }
        return $this->min;
    }

    public function getCount(): mixed
    {
        if ($this->count == null) {
            $this->count = self::createInstance();
        }

        return $this->count;
    }

    public function getFirst(): mixed
    {
        if ($this->first == null) {
            $this->first = self::createInstance();
        }

        return $this->first;
    }

    public function getLast(): mixed
    {
        if ($this->last == null) {
            $this->last = self::createInstance();
        }

        return $this->last;
    }

    public function getSum(): mixed
    {
        if ($this->sum == null) {
            $this->sum = self::createInstance();
        }

        return $this->sum;
    }

    public function getAverage(): mixed
    {
        if ($this->average != null) {
            return $this->average;
        }

        $valuesCount = count(TimeSeriesValuesHelper::getFieldsMapping($this->className));

        $sums = TimeSeriesValuesHelper::getValues($this->className, $this->sum);
        $counts = TimeSeriesValuesHelper::getValues($this->className, $this->count);
        $averages = array_fill(0, $valuesCount, null);


        for ($i = 0; $i < $valuesCount; $i++) {
            if ($counts[$i] < PHP_FLOAT_MIN) {
                $averages[$i] = NAN;
            } else {
                $averages[$i] = $sums[$i] / $counts[$i];
            }
        }

        $this->average = TimeSeriesValuesHelper::setFields($this->className, $averages);

        return $this->average;
    }

    public function getValuesFromMembers(): array
    {
        $valuesCount = count(TimeSeriesValuesHelper::getFieldsMapping($this->className));

        $result = array_fill(0, 6*$valuesCount, 0.0);
        $this->assignRollup($result, $this->first, 0);
        $this->assignRollup($result, $this->last, 1);
        $this->assignRollup($result, $this->min, 2);
        $this->assignRollup($result, $this->max, 3);
        $this->assignRollup($result, $this->sum, 4);
        $this->assignRollup($result, $this->count, 5);

        return $result;
    }

    private function assignRollup(array & $target, mixed $source, int $offset): void
    {
        if ($source != null) {
            $values = TimeSeriesValuesHelper::getValues($this->className, $source);
            if ($values != null) {
                for ($i = 0; $i < count($values); $i++) {
                    $target[$i * 6 + $offset] = $values[$i];
                }
            }
        }
    }

    public static function fromEntry(string $className, TimeSeriesEntry $entry): TypedTimeSeriesRollupEntry
    {
        $result = new TypedTimeSeriesRollupEntry($className, $entry->getTimestamp());
        $result->setRollup(true);
        $result->setTag($entry->getTag());

        $values = $entry->getValues();

        $result->first = TimeSeriesValuesHelper::setFields($className, self::extractValues($values, 0));
        $result->last = TimeSeriesValuesHelper::setFields($className, self::extractValues($values, 1));
        $result->min = TimeSeriesValuesHelper::setFields($className, self::extractValues($values, 2));
        $result->max = TimeSeriesValuesHelper::setFields($className, self::extractValues($values, 3));
        $result->sum = TimeSeriesValuesHelper::setFields($className, self::extractValues($values, 4));
        $result->count = TimeSeriesValuesHelper::setFields($className, self::extractValues($values, 5));

        return $result;
    }

    private static function extractValues(array $input, int $offset): array
    {
        $length = intval(ceil((count($input) - $offset) / 6.0));
        $idx = 0;
        $result = array_fill(0, $length, null);

        while ($idx < $length) {
            $result[$idx] = $input[$offset + $idx * 6];
            $idx++;
        }

        return $result;
    }
}

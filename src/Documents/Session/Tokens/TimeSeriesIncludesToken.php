<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Documents\Operations\TimeSeries\AbstractTimeSeriesRange;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesCountRange;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesRange;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesRangeType;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesTimeRange;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Primitives\NetISO8601Utils;
use RavenDB\Utils\StringBuilder;
use RavenDB\Utils\StringUtils;

class TimeSeriesIncludesToken extends QueryToken
{
    private ?string $sourcePath = null;
    private ?AbstractTimeSeriesRange $range = null;

    private function __construct(?string $sourcePath, ?AbstractTimeSeriesRange $range) {
        $this->range = $range;
        $this->sourcePath = $sourcePath;
    }

    public static function create(?string $sourcePath, ?AbstractTimeSeriesRange $range): TimeSeriesIncludesToken
    {
        return new TimeSeriesIncludesToken($sourcePath, $range);
    }

    public function addAliasToPath(?string $alias): void
    {
        $this->sourcePath = StringUtils::isEmpty($this->sourcePath)
                ? $alias
                : $alias . "." . $this->sourcePath;
    }

    public function writeTo(StringBuilder &$writer): void
    {
        $writer->append("timeseries(");

        if (StringUtils::isNotEmpty($this->sourcePath)) {
            $writer
                ->append($this->sourcePath)
                ->append(", ");
        }

        if (StringUtils::isNotEmpty($this->range->getName())) {
            $writer
                ->append("'")
                ->append($this->range->getName())
                ->append("'")
                ->append(", ");
        }

        if ($this->range instanceof TimeSeriesRange) {
            /** @var TimeSeriesRange $r */
            $r = $this->range;
            self::writeToTimeSeriesRange($writer, $r);
        } else if ($this->range instanceof TimeSeriesTimeRange) {
            /** @var TimeSeriesTimeRange $tr */
            $tr = $this->range;
            self::writeToTimeSeriesTimeRange($writer, $tr);
        } else if ($this->range instanceof TimeSeriesCountRange) {
            /** @var TimeSeriesCountRange $cr */
            $cr = $this->range;
            self::writeToTimeSeriesCountRange($writer, $cr);
        } else {
            $className = get_class($this->range);
            throw new IllegalArgumentException("Not supported time range type: " . $className );
        }

        $writer
                ->append(")");
    }

    private static function writeToTimeSeriesTimeRange(StringBuilder &$writer, TimeSeriesTimeRange $range): void
    {
        switch ($range->getType()) {
            case TimeSeriesRangeType::LAST:
                $writer
                    ->append("last(");
                break;
            default:
                throw new IllegalArgumentException("Not supported time range type: " . $range->getType());
        }

        $writer
            ->append($range->getTime()->getValue())
            ->append(", '")
            ->append($range->getTime()->getUnit())
            ->append("')");
    }

    private static function writeToTimeSeriesCountRange(StringBuilder $writer, TimeSeriesCountRange $range): void
    {
        switch ($range->getType()) {
            case TimeSeriesRangeType::LAST:
                $writer
                    ->append("last(");
                break;
            default:
                throw new IllegalArgumentException("Not supported time range type: " . $range->getType());
        }

        $writer
            ->append($range->getCount())
            ->append(")");
    }

    private static function writeToTimeSeriesRange(StringBuilder $writer, TimeSeriesRange $range): void
    {
        if ($range->getFrom() != null) {
            $writer
                ->append("'")
                ->append(NetISO8601Utils::format($range->getFrom(), true))
                ->append("'")
                ->append(", ");
        } else {
            $writer->append("null,");
        }

        if ($range->getTo() != null) {
            $writer
                ->append("'")
                ->append(NetISO8601Utils::format($range->getTo(), true))
                ->append("'");
        } else {
            $writer
                ->append("null");
        }
    }
}

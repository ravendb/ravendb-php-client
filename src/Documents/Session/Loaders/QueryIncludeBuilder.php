<?php

namespace RavenDB\Documents\Session\Loaders;

use DateTimeInterface;
use RavenDB\Constants\TimeSeries;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesRangeType;
use RavenDB\Primitives\TimeValue;
use RavenDB\Type\StringArray;

class QueryIncludeBuilder extends IncludeBuilderBase implements QueryIncludeBuilderInterface
{
    public function __construct(DocumentConventions $conventions)
    {
        parent::__construct($conventions);
    }

    /**
     * @param string|null $path
     * @param string|null $name
     * @return QueryIncludeBuilder
     */
    public function includeCounter(?string $path, ?string $name): QueryIncludeBuilder
    {
        $this->_includeCounterWithAlias($path, $name);
        return $this;
    }

    /**
     * @param string|null $pathOrNames
     * @param null|string|StringArray|array $names
     * @return QueryIncludeBuilder
     */
    public function includeCounters(?string $pathOrNames, $names = null): QueryIncludeBuilder
    {
        if ($names != null) {
            $this->_includeCounterWithAlias($pathOrNames, $names);
        } else {
            $this->_includeCounter("", $pathOrNames);
        }
        return $this;
    }


    public function includeAllCounters(?string $path = null): QueryIncludeBuilder
    {
        if ($path != null) {
            $this->_includeAllCountersWithAlias($path);
        } else {
            $this->_includeAllCounters("");
        }
        return $this;
    }


    public function includeDocuments(?string $path): QueryIncludeBuilder
    {
        $this->_includeDocuments($path);
        return $this;
    }

    public function includeTimeSeries(?string $name, ?DateTimeInterface $from = null, ?DateTimeInterface $to = null): QueryIncludeBuilder
    {
        return $this->includeTimeSeriesWithPath('', $name,$from, $to);
    }

    public function includeTimeSeriesWithPath(?string $path, ?string $name, ?DateTimeInterface $from = null, ?DateTimeInterface $to = null): QueryIncludeBuilder
    {
        $this->_withAlias();
        $this->_includeTimeSeriesFromTo($path, $name, $from, $to);
        return $this;
    }

    public function includeCompareExchangeValue(?string $path): QueryIncludeBuilder
    {
        $this->_includeCompareExchangeValue($path);
        return $this;
    }

    public function includeTimeSeriesRangeType(null | string | array $names, TimeSeriesRangeType $type, TimeValue | int $timeOrCount): QueryIncludeBuilder
    {
        if (is_string($names)) {
            if (is_int($timeOrCount)) {
//                $this->_includeTimeSeriesByRangeTypeAndCount("", $names, $type, $timeOrCount);
            } else {
//                $this->_includeTimeSeriesByRangeTypeAndTime("", $names, $type, $timeOrCount);
            }
        } else {
            if (is_int($timeOrCount)) {
//                $this->_includeArrayOfTimeSeriesByRangeTypeAndCount($names, $type, $timeOrCount);
            } else {
//                $this->_includeArrayOfTimeSeriesByRangeTypeAndTime($names, $type, $timeOrCount);
            }
        }

        return $this;
    }

    public function includeAllTimeSeries(TimeSeriesRangeType $type, TimeValue | int $timeOrCount): QueryIncludeBuilder
    {
        if (is_int($timeOrCount)) {
//          $this->_includeTimeSeriesByRangeTypeAndCount("", TimeSeries::ALL, $type, $timeOrCount);

        } else {
//            $this->_includeTimeSeriesByRangeTypeAndTime("", TimeSeries::ALL, $type, $timeOrCount);
        }

        return $this;
    }
}

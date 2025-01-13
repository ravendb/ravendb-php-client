<?php

namespace RavenDB\Documents\Session\Loaders;

use DateTime;
use DateTimeInterface;
use RavenDB\Constants\TimeSeries;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesRangeType;
use RavenDB\Primitives\TimeValue;

class IncludeBuilder extends IncludeBuilderBase implements IncludeBuilderInterface
{
    public function __construct(?DocumentConventions $conventions)
    {
        parent::__construct($conventions);
    }

    public function includeDocuments(?string $path): IncludeBuilder
    {
        $this->_includeDocuments($path);
        return $this;
    }

    public function includeCounter(?string $name): IncludeBuilderInterface
    {
        $this->_includeCounter("", $name);
        return $this;
    }

    public function includeCounters(array $names): IncludeBuilderInterface
    {
        $this->_includeCounters("", $names);
        return $this;
    }


    public function includeAllCounters(): IncludeBuilderInterface
    {
        $this->_includeAllCounters("");
        return $this;
    }

    public function includeTimeSeries(?string $name, ?DateTimeInterface $from = null, ?DateTimeInterface $to = null): IncludeBuilderInterface
    {
        $this->_includeTimeSeriesFromTo("", $name, $from, $to);
        return $this;
    }

    public function includeCompareExchangeValue(?string $path): IncludeBuilderInterface
    {
        $this->_includeCompareExchangeValue($path);
        return $this;
    }

    public function includeTimeSeriesByRange(null | string | array $names, TimeSeriesRangeType $type, TimeValue | int $timeOrCount): IncludeBuilderInterface
    {
        if (is_string($names)) {
            if (is_int($timeOrCount)) {
                $this->_includeTimeSeriesByRangeTypeAndCount("", $names, $type, $timeOrCount);
            } else {
                $this->_includeTimeSeriesByRangeTypeAndTime("", $names, $type, $timeOrCount);
            }
        } else {
            if (is_int($timeOrCount)) {
                $this->_includeArrayOfTimeSeriesByRangeTypeAndCount($names, $type, $timeOrCount);
            } else {
                $this->_includeArrayOfTimeSeriesByRangeTypeAndTime($names, $type, $timeOrCount);
            }
        }

        return $this;
    }

    public function includeAllTimeSeries(TimeSeriesRangeType $type, TimeValue | int $timeOrCount): IncludeBuilderInterface
    {
        if (is_int($timeOrCount)) {
          $this->_includeTimeSeriesByRangeTypeAndCount("", TimeSeries::ALL, $type, $timeOrCount);

        } else {
            $this->_includeTimeSeriesByRangeTypeAndTime("", TimeSeries::ALL, $type, $timeOrCount);
        }

        return $this;
    }

    /**
     * @obsolete
     */
    public function includeRevisionsWithPath(string $changeVectorPaths): IncludeBuilderInterface
    {
        return $this->includeRevisions($changeVectorPaths);
    }

    public function includeRevisions(string $changeVectorPaths): IncludeBuilderInterface
    {
        $this->_withAlias();
        $this->_includeRevisionsByChangeVectors($changeVectorPaths);
        return $this;
    }

    public function includeRevisionsBefore(DateTime $before): IncludeBuilderInterface
    {
        $this->_includeRevisionsBefore($before);
        return $this;
    }

}

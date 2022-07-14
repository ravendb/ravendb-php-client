<?php

namespace RavenDB\Documents\Session\Loaders;

use RavenDB\Documents\Conventions\DocumentConventions;
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

//    public function includeTimeSeries(?string name): QueryIncludeBuilder {
//        return includeTimeSeries(name, (Date) null, null);
//    }
//
//    public function includeTimeSeries(?string name, Date from, Date to): QueryIncludeBuilder {
//        _includeTimeSeriesFromTo("", name, from, to);
//        return this;
//    }
//
//    public function includeTimeSeries(?string path, ?string name): QueryIncludeBuilder {
//        return includeTimeSeries(path, name, null, null);
//    }
//
//    public function includeTimeSeries(?string path, ?string name, Date from, Date to): QueryIncludeBuilder {
//        _withAlias();
//        _includeTimeSeriesFromTo(path, name, from, to);
//        return this;
//    }

    public function includeCompareExchangeValue(?string $path): QueryIncludeBuilder
    {
        $this->_includeCompareExchangeValue($path);
        return $this;
    }

//    public function includeTimeSeries(?string name, TimeSeriesRangeType type, TimeValue time): QueryIncludeBuilder {
//        _includeTimeSeriesByRangeTypeAndTime("", name, type, time);
//        return this;
//    }
//
//    public function includeTimeSeries(?string name, TimeSeriesRangeType type, int count): QueryIncludeBuilder {
//        _includeTimeSeriesByRangeTypeAndCount("", name, type, count);
//        return this;
//    }
//
//    public function includeTimeSeries(?string[] names, TimeSeriesRangeType type, TimeValue time): QueryIncludeBuilder {
//        _includeArrayOfTimeSeriesByRangeTypeAndTime(names, type, time);
//        return this;
//    }
//
//    public function includeTimeSeries(?string[] names, TimeSeriesRangeType type, int count): QueryIncludeBuilder {
//        _includeArrayOfTimeSeriesByRangeTypeAndCount(names, type, count);
//        return this;
//    }
//
//    public function includeAllTimeSeries(TimeSeriesRangeType type, TimeValue time): QueryIncludeBuilder {
//        _includeTimeSeriesByRangeTypeAndTime("", Constants.TimeSeries.ALL, type, time);
//        return this;
//    }
//
//    public function includeAllTimeSeries(TimeSeriesRangeType type, int count): QueryIncludeBuilder {
//        _includeTimeSeriesByRangeTypeAndCount("", Constants.TimeSeries.ALL, type, count);
//        return this;
//    }
}

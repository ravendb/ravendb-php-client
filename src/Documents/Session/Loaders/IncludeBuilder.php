<?php

namespace RavenDB\Documents\Session\Loaders;

use DateTimeInterface;
use RavenDB\Documents\Conventions\DocumentConventions;

/**
 *
 */
class IncludeBuilder extends IncludeBuilderBase implements IncludeBuilderInterface
{
    public function __construct(?DocumentConventions $conventions)
    {
        parent::__construct($conventions);
    }

//    @Override
//    public IncludeBuilder includeDocuments(String path) {
//        _includeDocuments(path);
//        return this;
//    }
//
//    @Override
//    public IIncludeBuilder includeCounter(String name) {
//        _includeCounter("", name);
//        return this;
//    }
//
//    @Override
//    public IIncludeBuilder includeCounters(String[] names) {
//        _includeCounters("", names);
//        return this;
//    }
//
//    @Override
//    public IIncludeBuilder includeAllCounters() {
//        _includeAllCounters("");
//        return this;
//    }

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

//    @Override
//    public IIncludeBuilder includeTimeSeries(String name, TimeSeriesRangeType type, TimeValue time) {
//        _includeTimeSeriesByRangeTypeAndTime("", name, type, time);
//        return this;
//    }
//
//    @Override
//    public IIncludeBuilder includeTimeSeries(String name, TimeSeriesRangeType type, int count) {
//        _includeTimeSeriesByRangeTypeAndCount("", name, type, count);
//        return this;
//    }
//
//    @Override
//    public IIncludeBuilder includeTimeSeries(String[] names, TimeSeriesRangeType type, TimeValue time) {
//        _includeArrayOfTimeSeriesByRangeTypeAndTime(names, type, time);
//        return this;
//    }
//
//    @Override
//    public IIncludeBuilder includeTimeSeries(String[] names, TimeSeriesRangeType type, int count) {
//        _includeArrayOfTimeSeriesByRangeTypeAndCount(names, type, count);
//        return this;
//    }
//
//    @Override
//    public IIncludeBuilder includeAllTimeSeries(TimeSeriesRangeType type, TimeValue time) {
//        _includeTimeSeriesByRangeTypeAndTime("", Constants.TimeSeries.ALL, type, time);
//        return this;
//    }
//
//    @Override
//    public IIncludeBuilder includeAllTimeSeries(TimeSeriesRangeType type, int count) {
//        _includeTimeSeriesByRangeTypeAndCount("", Constants.TimeSeries.ALL, type, count);
//        return this;
//    }
}

<?php

namespace RavenDB\Documents\Session\Loaders;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Type\StringArray;
use RavenDB\Type\StringSet;
use RavenDB\Utils\StringUtils;

class IncludeBuilderBase
{
    protected int $nextParameterId = 1;

    protected ?DocumentConventions $conventions = null;
    public ?StringSet $documentsToInclude = null;

    public ?string $alias = null;
//    public Map<String, Tuple<Boolean, Set<String>>> countersToIncludeBySourcePath;

    /**
     * Structure:
     *  [
     *      'string index' => [
     *          bool value,
     *          [ array of strings ]
     *      ]
     *  ]
     */
    public array $countersToIncludeBySourcePath = [];

//    public Map<String, Set<AbstractTimeSeriesRange>> timeSeriesToIncludeBySourceAlias;
    public ?StringSet $compareExchangeValuesToInclude = null;
    public bool $includeTimeSeriesTags = false;
    public bool $includeTimeSeriesDocument = false;

//    public Set<AbstractTimeSeriesRange> getTimeSeriesToInclude() {
//        if (timeSeriesToIncludeBySourceAlias == null) {
//            return null;
//        }
//
//        return timeSeriesToIncludeBySourceAlias.get("");
//    }
//
    public function getCountersToInclude(): ?StringSet
    {
        if ($this->countersToIncludeBySourcePath == null) {
            return null;
        }

        if (!array_key_exists("", $this->countersToIncludeBySourcePath)) {
            return new StringSet();
        }

        return $this->countersToIncludeBySourcePath[""][1];
    }

    public function isAllCounters(): bool
    {
        if ($this->countersToIncludeBySourcePath == null) {
            return false;
        }

        $value = $this->countersToIncludeBySourcePath[""];
        return $value != null ? $value[0] : false;
    }

    public function __construct(?DocumentConventions $conventions)
    {
        $this->conventions = $conventions;
    }

    protected function _includeCompareExchangeValue(?string $path): void
    {
        if ($this->compareExchangeValuesToInclude == null) {
            $this->compareExchangeValuesToInclude = new StringSet();
        }

        $this->compareExchangeValuesToInclude->append($path);
    }

    /**
     * @param string|null $path
     * @param string|StringArray|array $names
     */
    protected function _includeCounterWithAlias(?string $path, $names): void
    {
        $this->_withAlias();

        if (is_string($names)) {
            $this->_includeCounter($path, $names);
        } else {
            $this->_includeCounters($path, $names);
        }
    }

    protected function _includeDocuments(?string $path): void
    {
        if ($this->documentsToInclude == null) {
            $this->documentsToInclude = new StringSet();
        }

        $this->documentsToInclude->append($path);
    }

    protected function _includeCounter(?string $path, ?string $name): void
    {
        if (StringUtils::isEmpty($name)) {
            throw new IllegalArgumentException("Name cannot be empty");
        }

        $this->assertNotAllAndAddNewEntryIfNeeded($path);

        $this->countersToIncludeBySourcePath[$path][1][] = $name;;
    }

    /**
     * @param string|null $path
     * @param StringArray|array|null $names
     */
    protected function _includeCounters(?string $path, $names): void
    {
        if ($names == null) {
            throw new IllegalArgumentException("Names cannot be null");
        }

        $this->assertNotAllAndAddNewEntryIfNeeded($path);

        foreach ($names as $name) {
            if (StringUtils::isBlank($name)) {
                throw new IllegalArgumentException("Counters(String[] names): 'names' should not contain null or whitespace elements");
            }

            $this->countersToIncludeBySourcePath[$path][1][] = $name;
        }
    }

    protected function _includeAllCountersWithAlias(?string $path): void
    {
        $this->_withAlias();
        $this->_includeAllCounters($path);
    }

    protected function _includeAllCounters(?string $sourcePath): void
    {
        $value = null;
        if (array_key_exists($sourcePath, $this->countersToIncludeBySourcePath)) {
            $value = $this->countersToIncludeBySourcePath[$sourcePath];
        }

        if ($value != null && $value[1] != null) {
            throw new IllegalStateException("You cannot use allCounters() after using counter(String name) or counters(String[] names)");
        }

        $this->countersToIncludeBySourcePath[$sourcePath] = [true, null];
    }

    protected function assertNotAllAndAddNewEntryIfNeeded(?string $path): void
    {
        if ($this->countersToIncludeBySourcePath != null) {
            $value = null;
            if (array_key_exists($path, $this->countersToIncludeBySourcePath)) {
                $value = $this->countersToIncludeBySourcePath[$path];
            }
            if ($value != null && $value[1]) {
                throw new IllegalStateException("You cannot use counter(name) after using allCounters()");
            }
        }

        if (!array_key_exists($path, $this->countersToIncludeBySourcePath)) {
            $this->countersToIncludeBySourcePath[$path] = [false, new StringSet()];
        }
    }

    protected function _withAlias(): void
    {
        if ($this->alias == null) {
            $alias = "a_" . ($this->nextParameterId++);
        }
    }

//    protected void _includeTimeSeriesFromTo(String alias, String name, Date from, Date to) {
//        assertValid(alias, name);
//
//        if (timeSeriesToIncludeBySourceAlias == null) {
//            timeSeriesToIncludeBySourceAlias = new HashMap<>();
//        }
//
//        Set<AbstractTimeSeriesRange> hashSet = timeSeriesToIncludeBySourceAlias.computeIfAbsent(alias, (key) -> new TreeSet<>(AbstractTimeSeriesRangeComparer.INSTANCE));
//
//        TimeSeriesRange range = new TimeSeriesRange();
//        range.setName(name);
//        range.setFrom(from);
//        range.setTo(to);
//
//        hashSet.add(range);
//    }
//
//    protected void _includeTimeSeriesByRangeTypeAndTime(String alias, String name, TimeSeriesRangeType type, TimeValue time) {
//        assertValid(alias, name);
//        assertValidType(type, time);
//
//        if (timeSeriesToIncludeBySourceAlias == null) {
//            timeSeriesToIncludeBySourceAlias = new HashMap<>();
//        }
//
//        Set<AbstractTimeSeriesRange> hashSet = timeSeriesToIncludeBySourceAlias
//                .computeIfAbsent(alias, a -> new TreeSet<>(AbstractTimeSeriesRangeComparer.INSTANCE));
//
//        TimeSeriesTimeRange timeRange = new TimeSeriesTimeRange();
//        timeRange.setName(name);
//        timeRange.setTime(time);
//        timeRange.setType(type);
//        hashSet.add(timeRange);
//    }
//
//    private static void assertValidType(TimeSeriesRangeType type, TimeValue time) {
//        switch (type) {
//            case NONE:
//                throw new IllegalArgumentException("Time range type cannot be set to NONE when time is specified.");
//            case LAST:
//                if (time != null) {
//                    if (time.getValue() <= 0) {
//                        throw new IllegalArgumentException("Time range type cannot be set to LAST when time is negative or zero.");
//                    }
//
//                    return;
//                }
//
//                throw new IllegalArgumentException("Time range type cannot be set to LAST when time is not specified.");
//            default:
//                throw new UnsupportedOperationException("Not supported time range type: " + type);
//        }
//    }
//
//    protected void _includeTimeSeriesByRangeTypeAndCount(String alias, String name, TimeSeriesRangeType type, int count) {
//        assertValid(alias, name);
//        assertValidTypeAndCount(type, count);
//
//        if (timeSeriesToIncludeBySourceAlias == null) {
//            timeSeriesToIncludeBySourceAlias = new HashMap<>();
//        }
//
//        Set<AbstractTimeSeriesRange> hashSet = timeSeriesToIncludeBySourceAlias.computeIfAbsent(alias, a -> new TreeSet<>(AbstractTimeSeriesRangeComparer.INSTANCE));
//
//        TimeSeriesCountRange countRange = new TimeSeriesCountRange();
//        countRange.setName(name);
//        countRange.setCount(count);
//        countRange.setType(type);
//
//        hashSet.add(countRange);
//    }
//
//    private static void assertValidTypeAndCount(TimeSeriesRangeType type, int count) {
//        switch (type) {
//            case NONE:
//                throw new IllegalArgumentException("Time range type cannot be set to NONE when count is specified.");
//            case LAST:
//                if (count <= 0) {
//                    throw new IllegalArgumentException("Count have to be positive.");
//                }
//                break;
//            default:
//                throw new UnsupportedOperationException("Not supported time range type: " + type);
//        }
//    }
//
//    protected void _includeArrayOfTimeSeriesByRangeTypeAndTime(String[] names, TimeSeriesRangeType type, TimeValue time) {
//        if (names == null) {
//            throw new IllegalArgumentException("Names cannot be null");
//        }
//
//        for (String name : names) {
//            _includeTimeSeriesByRangeTypeAndTime("", name, type, time);
//        }
//    }
//
//    protected void _includeArrayOfTimeSeriesByRangeTypeAndCount(String[] names, TimeSeriesRangeType type, int count) {
//        if (names == null) {
//            throw new IllegalArgumentException("Names cannot be null");
//        }
//
//        for (String name : names) {
//            _includeTimeSeriesByRangeTypeAndCount("", name, type, count);
//        }
//    }
//
//    private void assertValid(String alias, String name) {
//        if (StringUtils.isBlank(name)) {
//            throw new IllegalArgumentException("Name cannot be null or whitespace.");
//        }
//
//        if (timeSeriesToIncludeBySourceAlias != null) {
//            Set<AbstractTimeSeriesRange> hashSet2 = timeSeriesToIncludeBySourceAlias.get(alias);
//            if (hashSet2 != null && !hashSet2.isEmpty()) {
//                if (Constants.TimeSeries.ALL.equals(name)) {
//                    throw new IllegalArgumentException("IIncludeBuilder : Cannot use 'includeAllTimeSeries' after using 'includeTimeSeries' or 'includeAllTimeSeries'.");
//                }
//
//                if (hashSet2.stream().anyMatch(x -> Constants.TimeSeries.ALL.equals(x.getName()))) {
//                    throw new IllegalArgumentException("IIncludeBuilder : Cannot use 'includeTimeSeries' or 'includeAllTimeSeries' after using 'includeAllTimeSeries'.");
//                }
//            }
//        }
//    }

    public function getCompareExchangeValuesToInclude(): StringSet
    {
        return $this->compareExchangeValuesToInclude;
    }

//    public static class AbstractTimeSeriesRangeComparer implements Comparator<AbstractTimeSeriesRange> {
//        public final static AbstractTimeSeriesRangeComparer INSTANCE = new AbstractTimeSeriesRangeComparer();
//
//        private AbstractTimeSeriesRangeComparer() {
//        }
//
//        @Override
//        public int compare(AbstractTimeSeriesRange x, AbstractTimeSeriesRange y) {
//            String xName = x != null ? x.getName() : null;
//            String yName = y != null ? y.getName() : null;
//
//            return new CompareToBuilder()
//                    .append(xName, yName)
//                    .toComparison();
//        }
//    }
}

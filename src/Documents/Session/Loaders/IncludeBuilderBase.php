<?php

namespace RavenDB\Documents\Session\Loaders;

use DateTime;
use DateTimeInterface;
use RavenDB\Constants\TimeSeries;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\TimeSeries\AbstractTimeSeriesRangeSet;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesCountRange;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesRange;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesRangeType;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesTimeRange;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Exceptions\UnsupportedOperationException;
use RavenDB\Primitives\TimeValue;
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

    /** @var array<AbstractTimeSeriesRangeSet>|null  */
    public ?array $timeSeriesToIncludeBySourceAlias = null;
    public ?StringSet $compareExchangeValuesToInclude = null;

    public ?StringSet $revisionsToIncludeByChangeVector = null;
    public ?DateTime $revisionsToIncludeByDateTime = null;

    public bool $includeTimeSeriesTags = false;
    public bool $includeTimeSeriesDocument = false;

    public function getTimeSeriesToInclude(): ?AbstractTimeSeriesRangeSet
    {
        if ($this->timeSeriesToIncludeBySourceAlias == null) {
            return null;
        }

        return $this->timeSeriesToIncludeBySourceAlias[""];
    }

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

    public function getRevisionsToIncludeByChangeVector(): ?StringSet
    {
        return $this->revisionsToIncludeByChangeVector;
    }

    public function getRevisionsToIncludeByDateTime(): ?DateTime
    {
        return $this->revisionsToIncludeByDateTime;
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

    protected function _includeRevisionsBefore(?DateTime $revisionsToIncludeByDateTime): void
    {
        $this->revisionsToIncludeByDateTime = $revisionsToIncludeByDateTime;
    }

    protected function _includeRevisionsByChangeVectors(?string $path): void
    {
        if (StringUtils::isBlank($path)) {
            throw new IllegalArgumentException("Path cannot be null or whitespace");
        }

        if ($this->revisionsToIncludeByChangeVector == null) {
            $this->revisionsToIncludeByChangeVector = new StringSet();
        }

        $this->revisionsToIncludeByChangeVector->append($path);
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
            if ($value != null && $value[0]) {
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

    protected function _includeTimeSeriesFromTo(?string $alias, ?string $name, ?DateTimeInterface $from = null, ?DateTimeInterface $to = null): void
    {
        $this->assertValid($alias, $name);

        if ($this->timeSeriesToIncludeBySourceAlias == null) {
            $this->timeSeriesToIncludeBySourceAlias = [];
        }

        if (!array_key_exists($alias, $this->timeSeriesToIncludeBySourceAlias)) {
            $hashSet = new AbstractTimeSeriesRangeSet();
            $this->timeSeriesToIncludeBySourceAlias[$alias] = $hashSet;
        }
        /** @var AbstractTimeSeriesRangeSet $hashSet */
        $hashSet = $this->timeSeriesToIncludeBySourceAlias[$alias];

        $range = new TimeSeriesRange();
        $range->setName($name);
        $range->setFrom($from);
        $range->setTo($to);

        $hashSet[] = $range;
    }

    protected function _includeTimeSeriesByRangeTypeAndTime(?string $alias, ?string $name, TimeSeriesRangeType $type, TimeValue $time): void
    {
        $this->assertValid($alias, $name);
        self::assertValidType($type, $time);

        if ($this->timeSeriesToIncludeBySourceAlias == null) {
            $this->timeSeriesToIncludeBySourceAlias = [];
        }

        if (!array_key_exists($alias, $this->timeSeriesToIncludeBySourceAlias)) {
            $hashSet = new AbstractTimeSeriesRangeSet();
            $this->timeSeriesToIncludeBySourceAlias[$alias] = $hashSet;
        }
        /** @var AbstractTimeSeriesRangeSet $hashSet */
        $hashSet = $this->timeSeriesToIncludeBySourceAlias[$alias];

        $timeRange = new TimeSeriesTimeRange();
        $timeRange->setName($name);
        $timeRange->setTime($time);
        $timeRange->setType($type);
        $hashSet[] = $timeRange;
    }

    private static function assertValidType(TimeSeriesRangeType $type, ?TimeValue $time): void
    {
        switch ($type->getValue()) {
            case TimeSeriesRangeType::NONE:
                throw new IllegalArgumentException("Time range type cannot be set to NONE when time is specified.");
            case TimeSeriesRangeType::LAST:
                if ($time != null) {
                    if ($time->getValue() <= 0) {
                        throw new IllegalArgumentException("Time range type cannot be set to LAST when time is negative or zero.");
                    }

                    return;
                }

                throw new IllegalArgumentException("Time range type cannot be set to LAST when time is not specified.");
            default:
                throw new UnsupportedOperationException("Not supported time range type: " . $type);
        }
    }

    protected function _includeTimeSeriesByRangeTypeAndCount(string $alias, string $name, TimeSeriesRangeType $type, int $count): void
    {
        $this->assertValid($alias, $name);
        $this->assertValidTypeAndCount($type, $count);

        if ($this->timeSeriesToIncludeBySourceAlias == null) {
            $this->timeSeriesToIncludeBySourceAlias = [];
        }

        if (!array_key_exists($alias, $this->timeSeriesToIncludeBySourceAlias)) {
            $hashSet = new AbstractTimeSeriesRangeSet();
            $this->timeSeriesToIncludeBySourceAlias[$alias] = $hashSet;
        }
        /** @var AbstractTimeSeriesRangeSet $hashSet */
        $hashSet = $this->timeSeriesToIncludeBySourceAlias[$alias];

        $countRange = new TimeSeriesCountRange();
        $countRange->setName($name);
        $countRange->setCount($count);
        $countRange->setType($type);

        $hashSet[] = $countRange;
    }

    private static function assertValidTypeAndCount(TimeSeriesRangeType $type, int $count): void
    {
        switch ($type) {
            case TimeSeriesRangeType::NONE:
                throw new IllegalArgumentException("Time range type cannot be set to NONE when count is specified.");
            case TimeSeriesRangeType::LAST:
                if ($count <= 0) {
                    throw new IllegalArgumentException("Count have to be positive.");
                }
                break;
            default:
                throw new UnsupportedOperationException("Not supported time range type: " . $type);
        }
    }

    protected function _includeArrayOfTimeSeriesByRangeTypeAndTime(?array $names, TimeSeriesRangeType $type, TimeValue $time): void
    {
        if ($names == null) {
            throw new IllegalArgumentException("Names cannot be null");
        }

        foreach($names as $name) {
            $this->_includeTimeSeriesByRangeTypeAndTime("", $name, $type, $time);
        }
    }

    protected function _includeArrayOfTimeSeriesByRangeTypeAndCount(?array $names, TimeSeriesRangeType $type, int $count): void
    {
        if ($names == null) {
            throw new IllegalArgumentException("Names cannot be null");
        }

        foreach ($names as $name) {
            $this->_includeTimeSeriesByRangeTypeAndCount("", $name, $type, $count);
        }
    }

    private function assertValid(?string $alias, ?string $name): void
    {
        if (StringUtils::isBlank($name)) {
            throw new IllegalArgumentException("Name cannot be null or whitespace.");
        }

        if ($this->timeSeriesToIncludeBySourceAlias != null) {
            /** @var AbstractTimeSeriesRangeSet $hashSet2 */
            $hashSet2 = array_key_exists($alias, $this->timeSeriesToIncludeBySourceAlias) ? $this->timeSeriesToIncludeBySourceAlias[$alias] : null;
            if ($hashSet2 != null && !empty($hashSet2)) {
                if (TimeSeries::ALL == $name) {
                    throw new IllegalArgumentException("IncludeBuilderInterface : Cannot use 'includeAllTimeSeries' after using 'includeTimeSeries' or 'includeAllTimeSeries'.");
                }

                if (in_array(true, array_map(function($x) { return TimeSeries::ALL == $x->getName(); }, $hashSet2->getArrayCopy()))) {
                    throw new IllegalArgumentException("IncludeBuilderInterface : Cannot use 'includeTimeSeries' or 'includeAllTimeSeries' after using 'includeAllTimeSeries'.");
                }
            }
        }
    }

    public function getCompareExchangeValuesToInclude(): ?StringSet
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

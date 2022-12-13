<?php

namespace RavenDB\Documents\Session;

use Closure;
use DateTimeInterface;
use RavenDB\Constants\DocumentsMetadata;
use RavenDB\Documents\Commands\Batches\CommandType;
use RavenDB\Documents\Commands\Batches\TimeSeriesBatchCommandData;
use RavenDB\Documents\Operations\TimeSeries\AppendOperation;
use RavenDB\Documents\Operations\TimeSeries\DeleteOperation;
use RavenDB\Documents\Operations\TimeSeries\GetTimeSeriesOperation;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesRangeResult;
use RavenDB\Documents\Session\TimeSeries\TimeSeriesEntryArray;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Primitives\DatesComparator;
use RavenDB\Utils\StringUtils;

class SessionTimeSeriesBase
{
    protected ?string $docId = null;
    protected ?string $name = null;
    protected ?InMemoryDocumentSessionOperations $session = null;

    protected function __construct(?InMemoryDocumentSessionOperations $session, object|string|null $idOrEntity, ?string $name)
    {
        if (empty($idOrEntity)) {
            throw new IllegalArgumentException("DocumentId or entity cannot be null");
        }

        if (is_string($idOrEntity)) {
            $this->initWithDocumentId($session, $idOrEntity, $name);
            return;
        }

        $this->initWithEntity($session, $idOrEntity, $name);
    }

    private function initWithDocumentId(?InMemoryDocumentSessionOperations $session, ?string $documentId, ?string $name): void
    {
        if ($name == null) {
            throw new IllegalArgumentException("Name cannot be null");
        }

        $this->docId = $documentId;
        $this->name = $name;
        $this->session = $session;
    }

    private function initWithEntity(?InMemoryDocumentSessionOperations $session, ?object $entity, ?string $name): void
    {
        if ($entity == null) {
            throw new IllegalArgumentException("Entity cannot be null");
        }

        /** @var DocumentInfo $documentInfo */
        $documentInfo = $session->documentsByEntity->get($entity);
        if ($documentInfo == null) {
            $this->throwEntityNotInSession();
            return;
        }

        if (StringUtils::isBlank($name)) {
            throw new IllegalArgumentException("Name cannot be null or whitespace");
        }

        $this->docId = $documentInfo->getId();
        $this->name = $name;
        $this->session = $session;
    }


    public function append(DateTimeInterface $timestamp, float|array $values, ?string $tag = null): void
    {
        if (is_float($values)) {
            $values = [$values];
        }

        $documentInfo = $this->session->documentsById->getValue($this->docId);
        if ($documentInfo != null && $this->session->deletedEntities->contains($documentInfo->getEntity())) {
            $this->throwDocumentAlreadyDeletedInSession($this->docId, $this->name);
        }

        $op = new AppendOperation($timestamp, $values, $tag);

        $index = $this->session->deferredCommandsMap->getIndexFor($this->docId, CommandType::timeSeries(), $this->name);
        if ($index != null) {
            /** @var TimeSeriesBatchCommandData $command */
            $command = $this->session->deferredCommandsMap->get($index);

            $command->getTimeSeries()->append($op);
        } else {
            $appends = [$op];
            $this->session->defer(new TimeSeriesBatchCommandData($this->docId, $this->name, $appends, null));
        }
    }

    public function delete(?DateTimeInterface $from = null, null|DateTimeInterface|string $to = 'single'): void
    {
        /**
         * We must set second parameter to something different from null.
         *
         * Reason for this is that we have two different expected behaviors when second parameter is
         * actualy set to null by developer and not set at all.
         *
         *  - delete($from, null) - When $to parameter is set to null by developer - we want to delete all TimeSeries after $from
         *  - delete($from) - When $to parameter is not set by developer - we want to delete just TimeSeries at given timestamp set in $from
         */

        if (is_string($to)) {
            if ($to != 'single') {
                throw new IllegalArgumentException("Parameter \$to must be type of DateTimeInterface.");
            }
            $to = $from;
        }

        $documentInfo = $this->session->documentsById->getValue($this->docId);
        if ($documentInfo != null && $this->session->deletedEntities->contains($documentInfo->getEntity())) {
            $this->throwDocumentAlreadyDeletedInSession($this->docId, $this->name);
        }

        $op = new DeleteOperation($from, $to);

        $index = $this->session->deferredCommandsMap->getIndexFor($this->docId, CommandType::timeSeries(), $this->name);
        if ($index != null) {
            /** @var TimeSeriesBatchCommandData $command */
            $command = $this->session->deferredCommandsMap->get($index);

            $command->getTimeSeries()->delete($op);
        } else {
            $deletes = [ $op ];
            $this->session->defer(new TimeSeriesBatchCommandData($this->docId, $this->name, null, $deletes));
        }
    }

    private static function throwDocumentAlreadyDeletedInSession(?string $documentId, ?string $timeSeries): void
    {
        throw new IllegalStateException("Can't modify timeseries " . $timeSeries . " of document " . $documentId
                . ", the document was already deleted in this session.");
    }

    protected function throwEntityNotInSession(): void
    {
        throw new IllegalArgumentException("Entity is not associated with the session, cannot perform timeseries operations to it. " .
                "Use documentId instead or track the entity in the session.");
    }

    /**
     * @param DateTimeInterface|null $from
     * @param DateTimeInterface|null $to
     * @param Closure|null $includes
     * @param int $start
     * @param int $pageSize
     * @return TimeSeriesEntryArray|null
     */
    public function getTimeSeriesAndIncludes(?DateTimeInterface $from, ?DateTimeInterface $to,?Closure $includes, int $start, int $pageSize): ?TimeSeriesEntryArray
    {
        if ($pageSize == 0) {
            return new TimeSeriesEntryArray();
        }

        $document = $this->session->documentsById->getValue($this->docId);
        if ($document != null) {
            $metadata = $document->getMetadata();
            $metadataTimeSeriesRaw = array_key_exists(DocumentsMetadata::TIME_SERIES, $metadata) ? $metadata[DocumentsMetadata::TIME_SERIES] : null;
            if ($metadataTimeSeriesRaw != null && is_array($metadataTimeSeriesRaw)) {
                if (!in_array(strtolower($this->name), array_map('strtolower', $metadataTimeSeriesRaw))) {
                  // the document is loaded in the session, but the metadata says that there is no such timeseries
                    return new TimeSeriesEntryArray();
                }
            }
        }

        $this->session->incrementRequestCount();

        /** @var TimeSeriesRangeResult $rangeResult */
        $rangeResult = $this->session->getOperations()->send(new GetTimeSeriesOperation($this->docId, $this->name, $from, $to, $start, $pageSize, $includes), $this->session->getSessionInfo());

        if ($rangeResult == null) {
            return null;
        }

        if (!$this->session->noTracking) {
            $this->handleIncludes($rangeResult);

            // Map<String, List<TimeSeriesRangeResult>>
            if (!array_key_exists($this->docId, $this->session->getTimeSeriesByDocId())) {
                $this->session->getTimeSeriesByDocId()[$this->docId] = [];
            }
            // Map<String, List<TimeSeriesRangeResult>>
            $cache =  & $this->session->getTimeSeriesByDocId()[$this->docId];

            $ranges = null;
            if (array_key_exists($this->name, $cache)) {
                $ranges = & $cache[$this->name];
            };
            if (!empty($ranges)) {
                // update
                $addToStartOfArray = DatesComparator::compare(DatesComparator::leftDate($ranges[0]->getFrom()), DatesComparator::rightDate($to)) > 0;
                if ($addToStartOfArray) {
                    array_unshift($ranges, $rangeResult);
                } else {
                    $ranges[] = $rangeResult;
                }
            } else {
                $cache[$this->name] = [ $rangeResult ];
            }
        }

        return $rangeResult->getEntries();
    }

    private function handleIncludes(?TimeSeriesRangeResult $rangeResult): void
    {
        if ($rangeResult->getIncludes() == null) {
            return;
        }

        $this->session->registerIncludes($rangeResult->getIncludes());

        $rangeResult->setIncludes(null);
    }

//    private static List<TimeSeriesEntry> skipAndTrimRangeIfNeeded(Date from, Date to, TimeSeriesRangeResult fromRange,
//                                                                  TimeSeriesRangeResult toRange, List<TimeSeriesEntry> values,
//                                                                  int skip, int trim) {
//        if (fromRange != null && DatesComparator.compare(rightDate(fromRange.getTo()), leftDate(from)) >= 0) {
//            // need to skip a part of the first range
//            if (toRange != null && DatesComparator.compare(leftDate(toRange.getFrom()), rightDate(to)) <= 0) {
//                // also need to trim a part of the last range
//                return values.stream().skip(skip).limit(values.size() - skip - trim).collect(Collectors.toList());
//            }
//
//            return values.stream().skip(skip).collect(Collectors.toList());
//        }
//
//        if (toRange != null && DatesComparator.compare(leftDate(toRange.getFrom()), rightDate(to)) <= 0) {
//            // trim a part of the last range
//
//            return values.stream().limit(values.size() - trim).collect(Collectors.toList());
//        }
//
//        return values;
//    }

    /**
     * @param DateTimeInterface|null $from
     * @param DateTimeInterface|null $to
     * @param int $start
     * @param int $pageSize
     * @param Closure $includes
     * @return TimeSeriesEntryArray|null
     */
    protected function serveFromCache(
        ?DateTimeInterface $from,
        ?DateTimeInterface $to,
        int $start,
        int $pageSize,
        Closure $includes
    ): ?TimeSeriesEntryArray {
        return new TimeSeriesEntryArray();
//        Map<String, List<TimeSeriesRangeResult>> cache = session.getTimeSeriesByDocId().get(docId);
//        List<TimeSeriesRangeResult> ranges = cache.get(name);
//
//        // try to find a range in cache that contains [from, to]
//        // if found, chop just the relevant part from it and return to the user.
//
//        // otherwise, try to find two ranges (fromRange, toRange),
//        // such that 'fromRange' is the last occurence for which range.From <= from
//        // and 'toRange' is the first occurence for which range.To >= to.
//        // At the same time, figure out the missing partial ranges that we need to get from the server.
//
//        int toRangeIndex;
//        int fromRangeIndex = -1;
//
//        List<TimeSeriesRange> rangesToGetFromServer = null;
//
//        for (toRangeIndex = 0; toRangeIndex < ranges.size(); toRangeIndex++) {
//            if (DatesComparator.compare(leftDate(ranges.get(toRangeIndex).getFrom()), leftDate(from)) <= 0) {
//                if (DatesComparator.compare(rightDate(ranges.get(toRangeIndex).getTo()), rightDate(to)) >= 0
//                    || (ranges.get(toRangeIndex).getEntries().length - start >= pageSize)) {
//                    // we have the entire range in cache
//                    // we have all the range we need
//                    // or that we have all the results we need in smaller range
//
//                    return chopRelevantRange(ranges.get(toRangeIndex), from, to, start, pageSize);
//                }
//
//                fromRangeIndex = toRangeIndex;
//                continue;
//            }
//
//            // can't get the entire range from cache
//            if (rangesToGetFromServer == null) {
//                rangesToGetFromServer = new ArrayList<>();
//            }
//
//            // add the missing part [f, t] between current range start (or 'from')
//            // and previous range end (or 'to') to the list of ranges we need to get from server
//
//            Date fromToUse = toRangeIndex == 0 || DatesComparator.compare(rightDate(ranges.get(toRangeIndex - 1).getTo()), leftDate(from)) < 0
//                    ? from
//                    : ranges.get(toRangeIndex - 1).getTo();
//            Date toToUse = DatesComparator.compare(leftDate(ranges.get(toRangeIndex).getFrom()), rightDate(to)) <= 0
//                    ? ranges.get(toRangeIndex).getFrom()
//                    : to;
//
//            rangesToGetFromServer.add(new TimeSeriesRange(name, fromToUse, toToUse));
//
//            if (DatesComparator.compare(rightDate(ranges.get(toRangeIndex).getTo()), rightDate(to)) >= 0) {
//                break;
//            }
//        }
//
//        if (toRangeIndex == ranges.size()) {
//            // requested range [from, to] ends after all ranges in cache
//            // add the missing part between the last range end and 'to'
//            // to the list of ranges we need to get from server
//
//            if (rangesToGetFromServer == null) {
//                rangesToGetFromServer = new ArrayList<>();
//            }
//
//            rangesToGetFromServer.add(new TimeSeriesRange(name, ranges.get(ranges.size() - 1).getTo(), to));
//        }
//
//        // get all the missing parts from server
//
//        session.incrementRequestCount();
//
//        TimeSeriesDetails details = session.getOperations().send(new GetMultipleTimeSeriesOperation(docId, rangesToGetFromServer, start, pageSize, includes), session.sessionInfo);
//
//        if (includes != null) {
//            registerIncludes(details);
//        }
//        // merge all the missing parts we got from server
//        // with all the ranges in cache that are between 'fromRange' and 'toRange'
//
//        Reference<List<TimeSeriesEntry>> resultToUserRef = new Reference<>();
//        TimeSeriesEntry[] mergedValues = mergeRangesWithResults(from, to, ranges, fromRangeIndex, toRangeIndex, details.getValues().get(name), resultToUserRef);
//        List<TimeSeriesEntry> resultToUser = resultToUserRef.value;
//
//        if (!session.noTracking) {
//            from = details
//                    .getValues()
//                    .get(name)
//                    .stream()
//                    .map(x -> x.getFrom())
//                    .filter(Objects::nonNull)
//                    .min(Date::compareTo)
//                    .orElse(null);
//            to = details
//                    .getValues()
//                    .get(name)
//                    .stream()
//                    .map(x -> x.getTo())
//                    .filter(Objects::nonNull)
//                    .max(Date::compareTo)
//                    .orElse(null);
//            InMemoryDocumentSessionOperations.addToCache(name, from, to,
//                    fromRangeIndex, toRangeIndex, ranges, cache, mergedValues);
//        }
//
//        return resultToUser;
    }

//    private void registerIncludes(TimeSeriesDetails details) {
//        for (TimeSeriesRangeResult rangeResult : details.getValues().get(name)) {
//            handleIncludes(rangeResult);
//        }
//    }
//
//    private static TimeSeriesEntry[] mergeRangesWithResults(Date from, Date to, List<TimeSeriesRangeResult> ranges,
//                                                                int fromRangeIndex, int toRangeIndex,
//                                                                List<TimeSeriesRangeResult> resultFromServer,
//                                                                Reference<List<TimeSeriesEntry>> resultToUserRef) {
//        int skip = 0;
//        int trim = 0;
//        int currentResultIndex = 0;
//        List<TimeSeriesEntry> mergedValues = new ArrayList<>();
//
//        int start = fromRangeIndex != -1 ? fromRangeIndex : 0;
//        int end = toRangeIndex == ranges.size() ? ranges.size() - 1 : toRangeIndex;
//
//        for (int i = start; i <= end; i++) {
//            if (i == fromRangeIndex) {
//                if (DatesComparator.compare(leftDate(ranges.get(i).getFrom()), leftDate(from)) <= 0 &&
//                        DatesComparator.compare(leftDate(from), rightDate(ranges.get(i).getTo())) <= 0) {
//                    // requested range [from, to] starts inside 'fromRange'
//                    // i.e fromRange.From <= from <= fromRange.To
//                    // so we might need to skip a part of it when we return the
//                    // result to the user (i.e. skip [fromRange.From, from])
//
//                    if (ranges.get(i).getEntries() != null) {
//                        for (TimeSeriesEntry v : ranges.get(i).getEntries()) {
//                            mergedValues.add(v);
//                            if (DatesComparator.compare(definedDate(v.getTimestamp()), leftDate(from)) < 0) {
//                                skip++;
//                            }
//
//                        }
//                    }
//                }
//
//                continue;
//            }
//
//            if (currentResultIndex < resultFromServer.size()
//                    && DatesComparator.compare(leftDate(resultFromServer.get(currentResultIndex).getFrom()), leftDate(ranges.get(i).getFrom())) < 0) {
//                // add current result from server to the merged list
//                // in order to avoid duplication, skip first item in range
//                // (unless this is the first time we're adding to the merged list)
//                List<TimeSeriesEntry> toAdd = Arrays.stream(resultFromServer.get(currentResultIndex++)
//                        .getEntries())
//                        .skip(mergedValues.size() == 0 ? 0 : 1)
//                        .collect(Collectors.toList());
//                mergedValues.addAll(toAdd);
//            }
//
//            if (i == toRangeIndex) {
//                if (DatesComparator.compare(leftDate(ranges.get(i).getFrom()), rightDate(to)) <= 0) {
//                    // requested range [from, to] ends inside 'toRange'
//                    // so we might need to trim a part of it when we return the
//                    // result to the user (i.e. trim [to, toRange.to])
//
//                    for (int index = mergedValues.size() == 0 ? 0 : 1; index < ranges.get(i).getEntries().length; index++) {
//                        mergedValues.add(ranges.get(i).getEntries()[index]);
//                        if (DatesComparator.compare(definedDate(ranges.get(i).getEntries()[index].getTimestamp()), rightDate(to)) > 0) {
//                            trim++;
//                        }
//                    }
//                }
//
//                continue;
//            }
//
//            // add current range from cache to the merged list.
//            // in order to avoid duplication, skip first item in range if needed
//            List<TimeSeriesEntry> toAdd = Arrays.stream(ranges.get(i)
//                    .getEntries())
//                    .skip(mergedValues.size() == 0 ? 0 : 1)
//                    .collect(Collectors.toList());
//
//            mergedValues.addAll(toAdd);
//        }
//
//        if (currentResultIndex < resultFromServer.size()) {
//            // the requested range ends after all the ranges in cache,
//            // so the last missing part is from server
//            // add last missing part to the merged list
//
//            List<TimeSeriesEntry> toAdd = Arrays.stream(resultFromServer.get(currentResultIndex++)
//                    .getEntries())
//                    .skip(mergedValues.size() == 0 ? 0 : 1)
//                    .collect(Collectors.toList());
//            mergedValues.addAll(toAdd);
//        }
//
//        resultToUserRef.value = skipAndTrimRangeIfNeeded(from, to,
//                fromRangeIndex == -1 ? null : ranges.get(fromRangeIndex),
//                toRangeIndex == ranges.size() ? null : ranges.get(toRangeIndex),
//                mergedValues, skip, trim);
//
//        return mergedValues.toArray(new TimeSeriesEntry[0]);
//    }
//
//    private static List<TimeSeriesEntry> chopRelevantRange(TimeSeriesRangeResult range, Date from, Date to, int start, int pageSize) {
//        if (range.getEntries() == null) {
//            return Collections.emptyList();
//        }
//
//        List<TimeSeriesEntry> result = new ArrayList<>();
//        for (TimeSeriesEntry value : range.getEntries()) {
//            if (DatesComparator.compare(definedDate(value.getTimestamp()), rightDate(to)) > 0) {
//                break;
//            }
//            if (DatesComparator.compare(definedDate(value.getTimestamp()), leftDate(from)) < 0) {
//                continue;
//            }
//            if (start-- > 0) {
//                continue;
//            }
//
//            if (pageSize-- <= 0) {
//                break;
//            }
//
//            result.add(value);
//        }
//
//        return result;
//    }
//
//    protected <T> TimeSeriesEntry[] getFromCache(Date from, Date to, Consumer<ITimeSeriesIncludeBuilder> includes, int start, int pageSize) {
//        // RavenDB-16060
//        // Typed TimeSeries results need special handling when served from cache
//        // since we cache the results untyped
//
//        // in java we return untyped entries here
//
//        List<TimeSeriesEntry> resultToUser = serveFromCache(from, to, start, pageSize, includes);
//        if (resultToUser.isEmpty()) {
//            return new TimeSeriesEntry[0];
//        }
//
//        return resultToUser.toArray(new TimeSeriesEntry[0]);
//    }
//
    protected function notInCache(?DateTimeInterface $from, ?DateTimeInterface $to): bool
    {
        return true;
//        Map<String, List<TimeSeriesRangeResult>> cache = session.getTimeSeriesByDocId().get(docId);
//        if (cache == null) {
//            return true;
//        }
//
//        List<TimeSeriesRangeResult> ranges = cache.get(name);
//        if (ranges == null) {
//            return true;
//        }
//
//        return ranges.isEmpty()
//                || DatesComparator.compare(leftDate(ranges.get(0).getFrom()), rightDate(to)) > 0
//                || DatesComparator.compare(rightDate(ranges.get(ranges.size() - 1).getTo()), leftDate(from)) < 0;
    }

//    private static class CachedEntryInfo {
//        public boolean servedFromCache;
//        public List<TimeSeriesEntry> resultToUser;
//        public TimeSeriesEntry[] mergedValues;
//        public int fromRangeIndex;
//        public int toRangeIndex;
//
//        public CachedEntryInfo(boolean servedFromCache, List<TimeSeriesEntry> resultToUser, TimeSeriesEntry[] mergedValues, int fromRangeIndex, int toRangeIndex) {
//            $this->servedFromCache = servedFromCache;
//            $this->resultToUser = resultToUser;
//            $this->mergedValues = mergedValues;
//            $this->fromRangeIndex = fromRangeIndex;
//            $this->toRangeIndex = toRangeIndex;
//        }
//    }
}

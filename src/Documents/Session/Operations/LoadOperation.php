<?php

namespace RavenDB\Documents\Session\Operations;

use DateTime;
use InvalidArgumentException;
use RavenDB\Documents\Commands\GetDocumentsCommand;
use RavenDB\Documents\Commands\GetDocumentsResult;
use RavenDB\Documents\Operations\TimeSeries\AbstractTimeSeriesRangeSet;
use RavenDB\Documents\Session\DocumentInfo;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Type\ObjectArray;
use RavenDB\Type\StringArray;
use RavenDB\Utils\Logger;
use RavenDB\Utils\LoggerFactory;
use RavenDB\Utils\StringUtils;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class LoadOperation
{
    private InMemoryDocumentSessionOperations $session;

    private static ?Logger $logger = null;

    private ?StringArray $ids = null;
    private ?StringArray $includes = null;
    private ?StringArray $countersToInclude = null;
    private ?StringArray $revisionsToIncludeByChangeVector = null;
    private ?DateTime $revisionsToIncludeByDateTimeBefore = null;

    private ?StringArray $compareExchangeValuesToInclude = null;

    private bool  $includeAllCounters = false;
    private AbstractTimeSeriesRangeSet $timeSeriesToInclude;

    private bool $resultsSet = false;
    private GetDocumentsResult $results;

    public function __construct(InMemoryDocumentSessionOperations $session)
    {
        $this->session = $session;

        $this->ids = new StringArray();
        $this->includes = new StringArray();
        $this->countersToInclude = new StringArray();

        $this->timeSeriesToInclude = new AbstractTimeSeriesRangeSet();

        if (self::$logger == null) {
            self::$logger = LoggerFactory::getLogger(LoadOperation::class);
        }
    }

    /**
     * @throws IllegalStateException
     * @throws InvalidArgumentException
     */
    public function createRequest(): ?GetDocumentsCommand
    {
        if ($this->session->checkIfIdAlreadyIncluded($this->ids, $this->includes)) {
            return null;
        }

        $this->session->incrementRequestCount();

        if (self::$logger->isInfoEnabled()) {
            self::$logger->info(
              "Requesting the following ids " . join(",", $this->ids->getArrayCopy()) . " from " . $this->session->storeIdentifier()
            );
        }

        if ($this->includeAllCounters) {
            return GetDocumentsCommand::withAllCounters(
                $this->ids,
                $this->includes,
                true,
                $this->timeSeriesToInclude,
                $this->compareExchangeValuesToInclude,
                false
            );
        }

        return GetDocumentsCommand::withCounters(
            $this->ids,
            $this->includes,
            $this->countersToInclude,
            $this->revisionsToIncludeByChangeVector,
            $this->revisionsToIncludeByDateTimeBefore,
            $this->timeSeriesToInclude,
            $this->compareExchangeValuesToInclude,
            false
        );
    }

    public function byId(string $id): LoadOperation
    {
        if (empty($id)) {
            return $this;
        }

        if ($this->ids->count() == 0) {
            $this->ids->append($id);
        }

        return $this;
    }

    public function withIncludes(?StringArray $includes): LoadOperation
    {
        $this->includes = $includes;
        return $this;
    }

    public function withCompareExchange(?StringArray $compareExchangeValues): LoadOperation
    {
        if ($compareExchangeValues != null) {
            $this->compareExchangeValuesToInclude = $compareExchangeValues;
        }
        return $this;
    }

    public function withCounters(?StringArray $counters): LoadOperation
    {
        if ($counters != null) {
            $this->countersToInclude = $counters;
        }
        return $this;
    }

    public function withAllCounters(): LoadOperation
    {
        $this->includeAllCounters = true;
        return $this;
    }

    public function withRevisionsByChangeVector(?StringArray $revisionsByChangeVector): LoadOperation
    {
        if ($revisionsByChangeVector != null && !$revisionsByChangeVector->isEmpty()) {
            $this->revisionsToIncludeByChangeVector = $revisionsByChangeVector;
        }

        return $this;
    }

    public function withRevisionsByDateTimeBefore(?DateTime $revisionsByDateTimeBefore): LoadOperation
    {
        if ($revisionsByDateTimeBefore != null) {
            $this->revisionsToIncludeByDateTimeBefore = $revisionsByDateTimeBefore;
        }

        return $this;
    }

    public function withTimeSeries(?AbstractTimeSeriesRangeSet $timeSeries): LoadOperation
    {
        if ($timeSeries != null) {
            $this->timeSeriesToInclude = $timeSeries;
        }
        return $this;
    }

    public function byIds(null|array|StringArray $ids): LoadOperation
    {
        if (is_array($ids)) {
            $ids = StringArray::fromArray($ids);
        }

        $distinct = new StringArray();

        foreach($ids as $id) {
            if (!StringUtils::isBlank($id)) {
                $distinct[] = $id;
            }
        }

        $this->ids = $distinct;

        return $this;
    }


    /**
     * @throws ExceptionInterface
     * @throws IllegalStateException
     */
    public function getDocument(?string $className): ?object
    {
        if ($this->session->noTracking) {
            if (!$this->resultsSet && count($this->ids)) {
                throw new IllegalStateException('Cannot execute getDocument before operation execution.');
            }

            if (
                ($this->results == null) ||
                $this->results->getResults() == null ||
                (count($this->results->getResults()) == 0))
            {
                return null;
            }

            $document = $this->results->getResults()[0];
            if ($document == null) {
                return null;
            }

            $documentInfo = DocumentInfo::getNewDocumentInfo($document);
            return $this->session->trackEntity($className, $documentInfo);
        }

        return $this->getDocumentWithId($className, $this->ids[0]);
    }

    /**
     * @throws IllegalStateException
     * @throws ExceptionInterface
     */
    private function getDocumentWithId(?string $className, ?string $id = null): ?object
    {
        if (empty($id)) {
            return null; // new $className(); @todo: replace this with default value
        }

        if ($this->session->isDeleted($id)) {
            return null;// new $className(); @todo: replace this with default value that depends on type
        }

        $doc = $this->session->documentsById->getValue($id);
        if ($doc != null) {
            return $this->session->trackEntity($className, $doc);
        }

        $doc = $this->session->includedDocumentsById->getValue($id);
        if ($doc != null) {
            return $this->session->trackEntity($className, $doc);
        }

        return null; // new $className(); @todo: replace this with default value that depends on type
    }

    /**
     * @throws ExceptionInterface
     * @throws IllegalStateException
     */
    public function getDocuments($className): ObjectArray
    {
        $finalResults = new ObjectArray();

        if ($this->session->noTracking) {
            if (!$this->resultsSet && count($this->ids)) {
                throw new IllegalStateException("Cannot execute 'getDocuments' before operation execution.");
            }

            foreach ($this->ids as $id) {
                if (empty($id)) {
                    continue;
                }

                $finalResults->offsetSet($id, null);
            }

            if (($this->results == null) || $this->results->getResults() == null || !count($this->results->getResults())) {
                return $finalResults;
            }

            foreach ($this->results->getResults() as $document) {
                if ($document == null) { // @todo check this: if (document == null || document.isNull()) {
                    continue;
                }

                $newDocumentInfo = DocumentInfo::getNewDocumentInfo($document);
                $finalResults->offsetSet($newDocumentInfo->getId(), $this->session->trackEntity($className, $newDocumentInfo));
            }

            return $finalResults;
        }

        foreach ($this->ids as $id) {
            if ($id == null) {
                continue;
            }

            $finalResults->offsetSet($id, $this->getDocumentWithId($className, $id));
        }

        return $finalResults;
    }

    /**
     * @throws IllegalStateException
     */
    public function setResult(?GetDocumentsResult $result)
    {
        $this->resultsSet = true;

        if ($this->session->noTracking) {
            $this->results = $result;
            return;
        }
        if ($result == null) {
            $this->session->registerMissing($this->ids);
            return;
        }

        $this->session->registerIncludes($result->getIncludes());

        if ($this->includeAllCounters || count($this->countersToInclude)) {
            $this->session->registerCounters(
                $result->getCounterIncludes(),
                $this->ids,
                $this->countersToInclude,
                $this->includeAllCounters
            );
        }

        if ($this->timeSeriesToInclude != null) {
            $this->session->registerTimeSeries($result->getTimeSeriesIncludes());
        }

        if ($this->revisionsToIncludeByChangeVector !== null || $this->revisionsToIncludeByDateTimeBefore !== null) {
            $this->session->registerRevisionsIncludes($result->getRevisionIncludes());
        }

        if ($this->compareExchangeValuesToInclude != null) {
            $this->session->getClusterSession()->registerCompareExchangeValues($result->getCompareExchangeValueIncludes());
        }

        // JsonNode document
        foreach ($result->getResults() as $document) {
            if (empty($document)) {
                continue;
            }

            $newDocumentInfo = DocumentInfo::getNewDocumentInfo($document);
            $this->session->documentsById->add($newDocumentInfo);
        }

        foreach ($this->ids as $id) {
            $value = $this->session->documentsById->getValue($id);
            if ($value == null) {
                $this->session->registerMissing(StringArray::withValue($id));
            }
        }

        $this->session->registerMissingIncludes($result->getResults(), $result->getIncludes(), $this->includes);
    }
}

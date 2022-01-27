<?php

namespace RavenDB\Documents\Session\Operations;

use InvalidArgumentException;
use RavenDB\Documents\Commands\GetDocumentsCommand;
use RavenDB\Documents\Commands\GetDocumentsResult;
use RavenDB\Documents\Session\DocumentInfo;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Exceptions\IllegalStateException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use function PHPUnit\Framework\isEmpty;

class LoadOperation
{
    private InMemoryDocumentSessionOperations $session;

//    private static final Log logger = LogFactory.getLog(LoadOperation.class);

    private array $ids = [];
    private array $includes = [];

    private array $countersToInclude = [];
    private array $compareExchangeValuesToInclude = [];
    private bool  $includeAllCounters = false;
    private array $timeSeriesToInclude = [];


    private bool $resultsSet = false;
    private GetDocumentsResult $results;


    public function __construct(InMemoryDocumentSessionOperations $session)
    {
        $this->session = $session;
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

//        if (logger.isInfoEnabled()) {
//            logger.info(
//              "Requesting the following ids " + String.join(",", _ids) + " from " + _session.storeIdentifier()
//            );
//        }

        if ($this->includeAllCounters) {
//            return new GetDocumentsCommand(
//                _ids, _includes, true, _timeSeriesToInclude, _compareExchangeValuesToInclude, false
//            );
            return new GetDocumentsCommand($this->ids, $this->includes, true);
        }

//        return new GetDocumentsCommand(
//            _ids, _includes, _countersToInclude, _timeSeriesToInclude, _compareExchangeValuesToInclude, false
//        );
        return new GetDocumentsCommand($this->ids, $this->includes, false);
    }

    public function byId(string $id): LoadOperation
    {
        if (empty($id)) {
            return $this;
        }

        if (count($this->ids) == 0) {
            $this->ids[] = $id;
        }

        return $this;
    }

    public function withIncludes(array $includes): LoadOperation
    {
        $this->includes = $includes;
        return $this;
    }

    public function withCompareExchange(array $compareExchangeValues): LoadOperation
    {
        $this->compareExchangeValuesToInclude = $compareExchangeValues;
        return $this;
    }

    public function withCounters(?array $counters): LoadOperation
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

    public function withTimeSeries(?array $timeSeries): LoadOperation
    {
        if ($timeSeries != null) {
            $this->timeSeriesToInclude = $timeSeries;
        }
        return $this;
    }

    public function byIds(array $ids): LoadOperation
    {
        // @todo: check this TreeSet or we can leave array
        $distinct = []; //new TreeSet<>(String::compareToIgnoreCase);

        foreach($ids as $id) {
            if (!empty($id)) {
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
    public function getDocument(string $className)
    {
        if ($this->session->noTracking) {
            if (!$this->resultsSet && count($this->ids)) {
                throw new IllegalStateException('Cannot execute getDocument before operation execution.');
            }

            if (
                ($this->results != null) ||
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
    private function getDocumentWithId(string $className, ?string $id = null)
    {
        if (empty($id)) {
            return new $className();
        }

        if ($this->session->isDeleted($id)) {
            return new $className();
        }

        $doc = $this->session->documentsById->getValue($id);
        if ($doc != null) {
            return $this->session->trackEntity($className, $doc);
        }

        $doc = $this->session->includedDocumentsById->getValue($id);
        if ($doc != null) {
            return $this->session->trackEntity($className, $doc);
        }

        return new $className();
    }

    /**
     * @throws ExceptionInterface
     * @throws IllegalStateException
     */
    public function getDocuments($className): array
    {
        $finalResults = []; // new TreeMap<>(String::compareToIgnoreCase);

        if ($this->session->noTracking) {
            if (!$this->resultsSet && count($this->ids)) {
                throw new IllegalStateException("Cannot execute 'getDocuments' before operation execution.");
            }

            foreach ($this->ids as $id) {
                if (empty($id)) {
                    continue;
                }

                $finalResults[$id] = null;
            }

            if (($this->results == null) || $this->results->getResults() == null || !count($this->results->getResults())) {
                return $finalResults;
            }

            foreach ($this->results->getResults() as $document) {
                if ($document == null) { // @todo check this: if (document == null || document.isNull()) {
                    continue;
                }

                $newDocumentInfo = DocumentInfo::getNewDocumentInfo($document);
                $finalResults[$newDocumentInfo->getId()] = $this->session->trackEntity($className, $newDocumentInfo);
            }

            return $finalResults;
        }

        foreach ($this->ids as $id) {
            if ($id == null) {
                continue;
            }

            $finalResults[$id] = $this->getDocumentWithId($className, $id);
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
//            $this->session->registerCounters($result->getCounterIncludes(), $this->ids, $this->countersToInclude, $this->includeAllCounters);
        }

        if ($this->timeSeriesToInclude != null) {
//            $this->session->registerTimeSeries($result->getTimeSeriesIncludes());
        }

        if ($this->compareExchangeValuesToInclude != null) {
//            $this->session->getClusterSession()->registerCompareExchangeValues($result->getCompareExchangeValueIncludes());
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
                $this->session->registerMissing([$id]);
            }
        }

        $this->session->registerMissingIncludes($result->getResults(), $result->getIncludes(), $this->includes);
    }
}

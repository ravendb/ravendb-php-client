<?php

namespace RavenDB\Documents\Session\Operations;

use RavenDB\Documents\Commands\GetDocumentsCommand;
use RavenDB\Documents\Commands\GetDocumentsResult;
use RavenDB\Documents\Session\DocumentInfo;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;

class LoadOperation
{
    private InMemoryDocumentSessionOperations $session;

    private array $ids = [];
    private array $includes = [];

    private bool $resultsSet = false;
    private bool $includeAllCounters = false;

    private GetDocumentsResult $results;

    public function __construct(InMemoryDocumentSessionOperations $session)
    {
        $this->session = $session;
    }

    /**
     * @throws IllegalStateException
     * @throws IllegalArgumentException
     */
    public function createRequest(): ?GetDocumentsCommand
    {
//        if ($this->session->checkIfIdAlreadyIncluded($this->ids, $this->includes)) {
//            return null;
//        }

        $this->session->incrementRequestCount();

//        if (logger.isInfoEnabled()) {
//            logger.info("Requesting the following ids " + String.join(",", _ids) + " from " + _session.storeIdentifier());
//        }

        if ($this->includeAllCounters) {
//            return new GetDocumentsCommand(_ids, _includes, true, _timeSeriesToInclude, _compareExchangeValuesToInclude, false);
            return new GetDocumentsCommand($this->ids, $this->includes, true);
        }

//        return new GetDocumentsCommand(_ids, _includes, _countersToInclude, _timeSeriesToInclude, _compareExchangeValuesToInclude, false);
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

    public function setResult(?GetDocumentsResult $result)
    {
        $this->resultsSet = true;


//        if ($this->session->noTracking) {
//            $this->results = $result;
//            return;
//        }

        if ($result == null) {
            $this->session->registerMissing($this->ids);
            return;
        }

        $this->session->registerIncludes($result->getIncludes());

//        if (_includeAllCounters || _countersToInclude != null) {
//            _session.registerCounters(result.getCounterIncludes(), _ids, _countersToInclude, _includeAllCounters);
//        }
//
//        if (_timeSeriesToInclude != null) {
//            _session.registerTimeSeries(result.getTimeSeriesIncludes());
//        }
//
//        if (_compareExchangeValuesToInclude != null) {
//            _session.getClusterSession().registerCompareExchangeValues(result.getCompareExchangeValueIncludes());
//        }


            // JsonNode document
        foreach ($result->getResults() as $document) {
            if (empty($document)) { //  $document == null || $document->isNull()  todo: check what is this isNull in java
                continue;
            }

            $newDocumentInfo = DocumentInfo::getNewDocumentInfo($document);
            $this->session->documentsById->add($newDocumentInfo);
        }
//

//
//        for (String id : _ids) {
//            DocumentInfo value = _session.documentsById.getValue(id);
//            if (value == null) {
//                _session.registerMissing(id);
//            }
//        }
//
//        _session.registerMissingIncludes(result.getResults(), result.getIncludes(), _includes);
    }

    public function withAllCounters(): LoadOperation
    {
        $$this->includeAllCounters = true;
        return $this;
    }

    public function getDocument(string $className)
    {
//        if (_session.noTracking) {
//            if (!_resultsSet && _ids.length > 0) {
//                throw new IllegalStateException("Cannot execute getDocument before operation execution.");
//            }
//
//            if (_results == null || _results.getResults() == null || _results.getResults().size() == 0) {
//                return null;
//            }
//
//            ObjectNode document = (ObjectNode) _results.getResults().get(0);
//            if (document == null) {
//                return null;
//            }
//
//            DocumentInfo documentInfo = DocumentInfo.getNewDocumentInfo(document);
//            return _session.trackEntity(clazz, documentInfo);
//        }

        return $this->_getDocument($className, $this->ids[0]);
    }

    /**
     * @throws IllegalStateException
     */
    private function _getDocument(string $className, string $id)
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

        $doc = $this->session->includedDocumentsById->offsetGet($id);
        if ($doc != null) {
            return $this->session->trackEntity($className, $doc);
        }

        return new $className();
    }
}

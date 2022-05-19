<?php

namespace RavenDB\Documents\Session\Operations;

use RavenDB\Documents\Commands\GetDocumentsCommand;
use RavenDB\Documents\Commands\GetDocumentsResult;
use RavenDB\Documents\Session\DocumentInfo;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Type\ObjectArray;
use RavenDB\Type\StringList;

class LoadStartingWithOperation
{
    // @todo: logger feature - uncomment this when adding
//    private static final Log logger = LogFactory.getLog(LoadStartingWithOperation.class);
    private ?InMemoryDocumentSessionOperations $session = null;

    private ?string $startWith = null;
    private ?string $matches = null;
    private ?int $start = null;
    private ?int $pageSize = null;
    private ?string $exclude = null;
    private ?string $startAfter = null;

    private ?StringList $returnedIds = null;
    private bool $resultsSet = false;
    private ?GetDocumentsResult $results = null;

    public function __construct(?InMemoryDocumentSessionOperations $session) {
        $this->returnedIds = new StringList();
        $this->session = $session;
    }

    public function createRequest(): GetDocumentsCommand
    {
        $this->session->incrementRequestCount();

//        if (logger.isInfoEnabled()) {
//            logger.info("Requesting documents with ids starting with '" + _startWith + "' from " + _session.storeIdentifier());
//        }

        return GetDocumentsCommand::withStartWith($this->startWith, $this->startAfter, $this->matches, $this->exclude, $this->start, $this->pageSize, false);
    }

    public function withStartWith(
        ?string $idPrefix = null,
        ?string $matches = null,
        int $start = 0,
        int $pageSize = 25,
        ?string $exclude = null,
        ?string $startAfter = null): void
    {
        $this->startWith = $idPrefix;
        $this->matches = $matches;
        $this->start = $start;
        $this->pageSize = $pageSize;
        $this->exclude = $exclude;
        $this->startAfter = $startAfter;
    }

    public function setResult(GetDocumentsResult $result): void
    {
        $this->resultsSet = true;

        if ($this->session->noTracking) {
            $this->results = $result;
            return;
        }


        foreach ($result->getResults() as $document) {
            if (empty($document)) {
                continue;
            }

            $newDocumentInfo = DocumentInfo::getNewDocumentInfo($document);
            $this->session->documentsById->add($newDocumentInfo);
            $this->returnedIds->append($newDocumentInfo->getId());
        }
    }

    public function getDocuments(string $className): ObjectArray
    {
        $i = 0;
        $finalResults = new ObjectArray();

        if ($this->session->noTracking) {
            if ($this->results == null) {
                throw new IllegalStateException("Cannot execute getDocuments before operation execution");
            }

            if ($this->results == null || $this->results->getResults() == null || count($this->results->getResults()) == 0) {
                return $finalResults;
            }

            foreach ($this->results->getResults() as $document) {
                $newDocumentInfo = DocumentInfo::getNewDocumentInfo($document);
                $finalResults->append($this->session->trackEntity($className, $newDocumentInfo));
            }
        } else {
            foreach ($this->returnedIds as $id) {
                $finalResults->append($this->getDocument($className, $id));
            }
        }

        return $finalResults;
    }

    private function getDocument(string $className, ?string $id): ?object
    {
        if ($id == null) {
            return null;
        }

        if ($this->session->isDeleted($id)) {
            return null;
        }

        $doc = $this->session->documentsById->getValue($id);
        if ($doc != null) {
            return $this->session->trackEntity($className, $doc);
        }

        return null;
    }
}

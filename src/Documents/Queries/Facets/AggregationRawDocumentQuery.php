<?php

namespace RavenDB\Documents\Queries\Facets;

use RavenDB\Documents\Queries\IndexQuery;
use RavenDB\Documents\Queries\QueryResult;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Documents\Session\RawDocumentQueryInterface;
use RavenDB\Exceptions\IllegalArgumentException;

class AggregationRawDocumentQuery extends AggregationQueryBase
{
    private ?RawDocumentQueryInterface $source = null;

    public function __construct(?RawDocumentQueryInterface $source, ?InMemoryDocumentSessionOperations $session)
    {
        parent::__construct($session);
        $this->source = $source;

        if ($source == null) {
            throw new IllegalArgumentException("Source cannot be null");
        }
    }

    protected function getIndexQuery(bool $updateAfterQueryExecuted = false): IndexQuery
    {
        return $this->source->getIndexQuery();
    }

    protected function invokeAfterQueryExecuted(QueryResult $result): void
    {
        $this->source->invokeAfterQueryExecuted($result);
    }
}

<?php

namespace RavenDB\Documents\Queries\Facets;

use Closure;
use RavenDB\Documents\Commands\QueryCommand;
use RavenDB\Documents\Lazy;
use RavenDB\Documents\Queries\IndexQuery;
use RavenDB\Documents\Queries\QueryResult;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Documents\Session\Operations\Lazy\LazyAggregationQueryOperation;
use RavenDB\Documents\Session\Operations\QueryOperation;
use RavenDB\Extensions\JsonExtensions;
use RavenDB\Utils\Stopwatch;

abstract class AggregationQueryBase
{
    private ?InMemoryDocumentSessionOperations $session = null;
    private ?IndexQuery $query = null;
    private ?Stopwatch $duration = null;

    protected function __construct(InMemoryDocumentSessionOperations $session) {
        $this->session = $session;
    }

    public function execute(): FacetResultArray
    {
        $command = $this->getCommand();

        $this->duration = Stopwatch::createStarted();

        $this->session->incrementRequestCount();
        $this->session->getRequestExecutor()->execute($command);

        /** @var QueryResult $queryResult */
        $queryResult = $command->getResult();
        return $this->processResults($queryResult);
    }

    public function executeLazy(?Closure $onEval = null): Lazy
    {
        $this->query = $this->getIndexQuery();
        $t = $this;
        return $this->session->addLazyOperation(null,
            new LazyAggregationQueryOperation($this->session, $this->query, function($result) use ($t) { $t->invokeAfterQueryExecuted($result); }, Closure::fromCallable([$this, 'processResults'])), $onEval);
    }

    protected abstract function getIndexQuery(bool $updateAfterQueryExecuted = true): IndexQuery;

    protected abstract function invokeAfterQueryExecuted(QueryResult $result): void;

    private function processResults(QueryResult $queryResult): FacetResultArray
    {
        $this->invokeAfterQueryExecuted($queryResult);

        $results = new FacetResultArray();
        foreach ($queryResult->getResults() as $result) {
            $facetResult = JsonExtensions::getDefaultMapper()->denormalize($result, FacetResult::class);
            $results->offsetSet($facetResult->getName(), $facetResult);
        }

        $this->session->registerIncludes($queryResult->getIncludes());

        QueryOperation::ensureIsAcceptable($queryResult, $this->query->isWaitForNonStaleResults(), $this->duration, $this->session);
        return $results;
    }

    private function getCommand(): QueryCommand
    {
        $this->query = $this->getIndexQuery();

        return new QueryCommand($this->session, $this->query, false, false);
    }

    public function toString(): string
    {
        return $this->getIndexQuery(false)->toString();
    }
}

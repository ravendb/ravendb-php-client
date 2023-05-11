<?php

namespace RavenDB\Documents\Queries\Suggestions;

use Closure;
use RavenDB\Documents\Commands\QueryCommand;
use RavenDB\Documents\Lazy;
use RavenDB\Documents\Queries\IndexQuery;
use RavenDB\Documents\Queries\QueryResult;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Documents\Session\Operations\QueryOperation;
use RavenDB\Extensions\JsonExtensions;
use RavenDB\Utils\Stopwatch;
use RuntimeException;
use Throwable;

abstract class SuggestionQueryBase
{
    private ?InMemoryDocumentSessionOperations $session = null;
    private ?IndexQuery $query = null;
    private ?Stopwatch $duration = null;

    protected function __construct(?InMemoryDocumentSessionOperations $session)
    {
        $this->session = $session;
    }

    public function execute(): array
    {
        $command = $this->getCommand();

        $this->duration = Stopwatch::createStarted();
        $this->session->incrementRequestCount();
        $this->session->getRequestExecutor()->execute($command);

        return $this->processResults($command->getResult());
    }

    private function processResults(QueryResult $queryResult): array
    {
        $this->invokeAfterQueryExecuted($queryResult);

        try {
            $results = [];
            foreach ($queryResult->getResults() as $result) {
                $suggestionResult = JsonExtensions::getDefaultMapper()->denormalize($result, SuggestionResult::class);
                $results[$suggestionResult->getName()] = $suggestionResult;
            }

            QueryOperation::ensureIsAcceptable($queryResult, $this->query->isWaitForNonStaleResults(), $this->duration, $this->session);

            return $results;
        } catch (Throwable $e) {
            throw new RuntimeException("Unable to process suggestions results: " . $e->getMessage(), $e);
        }
    }

    public function executeLazy(Closure $onEval = null): Lazy
    {
        $this->query = $this->getIndexQuery();

//        return $this->session->addLazyOperation(null,
//                new LazySuggestionQueryOperation($this->session, $this->query, this::invokeAfterQueryExecuted, this::processResults), $onEval);
    }

    protected abstract function getIndexQuery(bool $updateAfterQueryExecuted = true): IndexQuery;

    protected abstract function invokeAfterQueryExecuted(?QueryResult $result): void;

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

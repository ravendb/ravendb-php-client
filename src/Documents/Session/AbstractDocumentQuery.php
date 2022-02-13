<?php

namespace RavenDB\Documents\Session;

use RavenDB\Documents\Session\Operations\QueryOperation;
use RavenDB\Documents\Session\Tokens\DeclareTokenArray;
use RavenDB\Documents\Session\Tokens\LoadTokenList;
use RavenDB\Primitives\CleanCloseable;

abstract class AbstractDocumentQuery implements AbstractDocumentQueryInterface
{
    protected string $className;

    protected string $queryRaw = '';

    protected ?int $pageSize = null;

    protected int $start = 0;

    protected function __construct(
        string $className,
        InMemoryDocumentSessionOperations $session,
        ?string $indexName,
        ?string $collectionName,
        bool $isGroupBy,
        ?DeclareTokenArray $declareTokens,
        ?LoadTokenList $loadTokens,
        ?string $fromAlias = null,
        bool $isProjectInto = false
    ) {
        $this->className = $className;
//        rootTypes.add(clazz);
//        this.isGroupBy = isGroupBy;
//        this.indexName = indexName;
//        this.collectionName = collectionName;
//        this.fromToken = FromToken.create(indexName, collectionName, fromAlias);
//        this.declareTokens = declareTokens;
//        this.loadTokens = loadTokens;
//        theSession = session;
//        _addAfterQueryExecutedListener(this::updateStatsHighlightingsAndExplanations);
//        _conventions = session == null ? new DocumentConventions() : session.getConventions();
//        this.isProjectInto = isProjectInto != null ? isProjectInto : false;
    }

    protected ?QueryOperation $queryOperation = null;

    public function getQueryOperation(): ?QueryOperation
    {
        return $this->queryOperation;
    }

    public function take(int $count): void
    {
        $this->pageSize = $count;
    }

    public function skip(int $count): void
    {
        $this->start = $count;
    }

    public function toList(): array
    {
        return $this->executeQueryOperation(null);
    }

    private function executeQueryOperation(?int $take = null): array
    {
        $this->executeQueryOperationInternal($take);

        return [];//$this->queryOperation->complete($this->className);
    }

    private function executeQueryOperationAsArray(?int $take): array
    {
        $this->executeQueryOperationInternal($take);

        return [];//$this->queryOperation->completeAsArray($this->className);
    }

    private function executeQueryOperationInternal(?int $take): void {
        if ($take != null && ($this->pageSize == null || $this->pageSize > $take)) {
            $this->take($take);
        }

        $this->initSync();
    }

    protected function initSync(): void
    {
        if ($this->queryOperation != null) {
            return;
        }

        $this->queryOperation = $this->initializeQueryOperation();
        $this->executeActualQuery();
    }

    private function executeActualQuery(): void
    {
//        /** @var CleanCloseable $context */
//        $context = $this->queryOperation->enterQueryContext();
//        try {
//            /** QueryCommand */
//            $command = $this->queryOperation->createRequest();
//            $this->theSession->getRequestExecutor()->execute($command, $this->theSession->sessionInfo);
//            $this->queryOperation->setResult($command->getResult());
//        } finally {
//            $context->close();
//        }
//
//        $this->invokeAfterQueryExecuted($this->queryOperation->getCurrentQueryResults());
    }

    public function initializeQueryOperation(): QueryOperation
    {
//        /** @var BeforeQueryEventArgs  $beforeQueryExecutedEventArgs */
//        $beforeQueryExecutedEventArgs = new BeforeQueryEventArgs($this->theSession, new DocumentQueryCustomizationDelegate($this));
//        $this->theSession->onBeforeQueryInvoke($beforeQueryExecutedEventArgs);
//
//        $indexQuery = $this->getIndexQuery();
//
//        return new QueryOperation(
//            $this->theSession,
//            $his->indexName,
//            $indexQuery,
//            $this->fieldsToFetchToken,
//            $this->disableEntitiesTracking,
//            false,
//            false,
//            $this->isProjectInto
//        );
    }
}

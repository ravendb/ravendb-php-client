<?php

namespace RavenDB\Documents\Session\Operations;

use RavenDB\Documents\Commands\Batches\ClusterWideBatchCommand;
use RavenDB\Documents\Commands\Batches\SingleNodeBatchCommand;
use RavenDB\Documents\Session\ActionsToRunOnSuccess;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Json\BatchCommandResult;

// @todo: Implement this
class BatchOperation
{
    private InMemoryDocumentSessionOperations $session;

    private array $entities;
    private int $sessionCommandsCount;
    private int $allCommandsCount;
    private ActionsToRunOnSuccess $onSuccessfulRequest;

    public function __construct(InMemoryDocumentSessionOperations $session)
    {
        $this->session = $session;
    }

    /**
     * @throws IllegalArgumentException
     */
    public function createRequest(): ?SingleNodeBatchCommand
    {
        $result = $this->session->prepareForSaveChanges();

        $this->onSuccessfulRequest = $result->getOnSuccess();

        $this->sessionCommandsCount = count($result->getSessionCommands());

        foreach ($result->getDeferredCommands() as $deferredCommand) {
            $result->getSessionCommands()[] = $deferredCommand;
        }

//        @todo: implement this validation
//        $this->session->validateClusterTransaction($result);

        $this->allCommandsCount = count($result->getSessionCommands());

        if ($this->allCommandsCount == 0) {
            return null;
        }

        $this->session->incrementRequestCount();

        $this->entities = $result->getEntities();

        if ($this->session->getTransactionMode()->isClusterWide()) {
            return new ClusterWideBatchCommand(
                $this->session->getConventions(),
                $result->getSessionCommands(),
                $result->getOptions(),
                $this->session->disableAtomicDocumentWritesInClusterWideTransaction
            );
        }

        return new SingleNodeBatchCommand(
            $this->session->getConventions(),
            $result->getSessionCommands(),
            $result->getOptions()
        );
    }

    public function setResult($result): void
    {
        $this->onSuccessfulRequest->clearSessionStateAfterSuccessfulSaveChanges();
    }
}

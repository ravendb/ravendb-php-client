<?php

namespace RavenDB\Documents\Session;

use InvalidArgumentException;
use Ramsey\Uuid\UuidInterface;
use RavenDB\Documents\Commands\GetDocumentsResult;
use RavenDB\Documents\DocumentStore;
use RavenDB\Documents\Linq\DocumentQueryGeneratorInterface;
use RavenDB\Documents\Session\Operations\BatchOperation;
use RavenDB\Documents\Session\Operations\LoadOperation;
use RavenDB\Exceptions\IllegalStateException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class DocumentSession extends InMemoryDocumentSessionOperations implements
    AdvancedSessionOperationsInterface,
    DocumentSessionImplementationInterface,
    DocumentQueryGeneratorInterface
{
    private ?ClusterTransactionOperationsInterface $clusterTransaction = null;

    public function __construct(DocumentStore $documentStore, UuidInterface $sessionId, SessionOptions $options)
    {
        parent::__construct($documentStore, $sessionId, $options);
    }

    public function advanced(): AdvancedSessionOperationsInterface
    {
        return $this;
    }

    /**
     * @throws IllegalStateException
     * @throws InvalidArgumentException
     * @throws ExceptionInterface
     */
    public function load(string $className, string $id): object
    {
        if (empty($id)) {
            return new $className();
        }

        $loadOperation = new LoadOperation($this);

        $loadOperation->byId($id);

        $command = $loadOperation->createRequest();

        if ($command != null) {
            $this->requestExecutor->execute($command, $this->sessionInfo);

            /** @var GetDocumentsResult $result */
            $result = $command->getResult();
            $loadOperation->setResult($result);
        }

        return $loadOperation->getDocument($className);
    }

    /**
     * @throws IllegalStateException
     * @throws \RavenDB\Exceptions\IllegalArgumentException
     */
    public function saveChanges(): void
    {
        $saveChangeOperation = new BatchOperation($this);

        $command = $saveChangeOperation->createRequest();

        if ($command == null) {
            return;
        }

        if ($this->noTracking) {
            throw new IllegalStateException("Cannot execute saveChanges when entity tracking is disabled in session.");
        }

        try {
            $this->requestExecutor->execute($command, $this->sessionInfo);
            $this->updateSessionAfterSaveChanges($command->getResult());
            $saveChangeOperation->setResult($command->getResult());
        } finally {
            $command->close();
        }
    }

    protected function generateId(?object $entity): string
    {
        // TODO: Implement generateId() method.
        return "12345";
    }

    public function clusterTransaction(): ClusterTransactionOperationsInterface
    {
        if ($this->clusterTransaction == null) {
            $this->clusterTransaction = new  ClusterTransactionOperations($this);
        }

        return $this->clusterTransaction;
    }

    protected function hasClusterSession(): bool
    {
        return $this->clusterTransaction != null;
    }

    protected function clearClusterSession(): void
    {
        if (!$this->hasClusterSession()) {
            return;
        }

        // @todo: implement this
        $this->getClusterSession()->clear();
    }

    public function getClusterSession(): ClusterTransactionOperationsBase
    {
        if ($this->clusterTransaction == null) {
            $this->clusterTransaction = new ClusterTransactionOperations($this);
        }
        return $this->clusterTransaction;
    }
}

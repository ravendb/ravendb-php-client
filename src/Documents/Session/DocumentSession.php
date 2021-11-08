<?php

namespace RavenDB\Documents\Session;

use phpDocumentor\Reflection\Types\Callable_;
use phpDocumentor\Reflection\Types\Mixed_;
use Ramsey\Uuid\UuidInterface;
use RavenDB\Documents\Commands\GetDocumentsResult;
use RavenDB\Documents\DocumentStore;
use RavenDB\Documents\Linq\DocumentQueryGeneratorInterface;
use RavenDB\Documents\Session\Operations\LoadOperation;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;

class DocumentSession extends InMemoryDocumentSessionOperations implements
    AdvancedSessionOperationsInterface,
    DocumentSessionImplementationInterface,
    DocumentQueryGeneratorInterface
{
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
     * @throws IllegalArgumentException
     */
    public function load(string $className, string $id)
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

    public function saveChanges(): void
    {
        // todo: implement this
    }

    protected function generateId(?object $entity): string
    {
        // TODO: Implement generateId() method.
        return "12345";
    }
}

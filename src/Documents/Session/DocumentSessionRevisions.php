<?php

namespace RavenDB\Documents\Session;

use DateTime;
use RavenDB\Documents\Session\Operations\GetRevisionOperation;
use RavenDB\Documents\Session\Operations\GetRevisionsCountOperation;
use RavenDB\Documents\Session\Operations\Lazy\LazyRevisionOperations;
use RavenDB\Type\StringArray;

class DocumentSessionRevisions extends DocumentSessionRevisionsBase implements RevisionsSessionOperationsInterface
{
    public function __construct(?InMemoryDocumentSessionOperations $session)
    {
        parent::__construct($session);
    }

    public function lazily(): LazyRevisionsOperationsInterface
    {
        return new LazyRevisionOperations($this->session);
    }

    public function getFor(?string $className, ?string $id, int $start = 0, int $pageSize = 25): array
    {
        $operation = GetRevisionOperation::withPagination($this->session, $id, $start, $pageSize);

        $command = $operation->createRequest();
        if ($command == null) {
            return $operation->getRevisionsFor($className);
        }
        if ($this->sessionInfo != null) {
            $this->sessionInfo->incrementRequestCount();
        }
        $this->requestExecutor->execute($command, $this->sessionInfo);
        $operation->setResult($command->getResult());
        return $operation->getRevisionsFor($className);
    }

    public function getMetadataFor(?string $id, int $start = 0, int $pageSize = 25): array
    {
        $operation = GetRevisionOperation::withPagination($this->session, $id, $start, $pageSize, true);
        $command = $operation->createRequest();
        if ($command == null) {
            return $operation->getRevisionsMetadataFor();
        }
        if ($this->sessionInfo != null) {
            $this->sessionInfo->incrementRequestCount();
        }
        $this->requestExecutor->execute($command, $this->sessionInfo);
        $operation->setResult($command->getResult());
        return $operation->getRevisionsMetadataFor();
    }

    public function get(?string $className, null|string|array|StringArray $changeVectors): mixed
    {
        if (is_null($changeVectors) || is_string($changeVectors)) {
            return $this->getSingle($className, $changeVectors);
        }

        return $this->getMultiple($className, $changeVectors);
    }
    public function getSingle(?string $className, ?string $changeVector): ?object
    {
        $operation = GetRevisionOperation::forChangeVector($this->session, $changeVector);

        $command = $operation->createRequest();
        if ($command == null) {
            return $operation->getRevision($className);
        }
        if ($this->sessionInfo != null) {
            $this->sessionInfo->incrementRequestCount();
        }
        $this->requestExecutor->execute($command, $this->sessionInfo);
        $operation->setResult($command->getResult());
        return $operation->getRevisionFromResult($className);
    }

    public function getMultiple(?string $className, StringArray|array $changeVectors): array
    {
        if (is_array($changeVectors)) {
            $changeVectors = StringArray::fromArray($changeVectors);
        }

        $operation = GetRevisionOperation::forChangeVectors($this->session, $changeVectors);

        $command = $operation->createRequest();
        if ($command == null) {
            return $operation->getRevisions($className);
        }
        if ($this->sessionInfo != null) {
            $this->sessionInfo->incrementRequestCount();
        }
        $this->requestExecutor->execute($command, $this->sessionInfo);
        $operation->setResult($command->getResult());
        return $operation->getRevisions($className);
    }

    public function getBeforeDate(?string $className, ?string $id, ?DateTime $date): ?object
    {
        $operation = GetRevisionOperation::beforeDate($this->session, $id, $date);
        $command = $operation->createRequest();
        if ($command == null) {
            return $operation->getRevision($className);
        }
        if ($this->sessionInfo != null) {
            $this->sessionInfo->incrementRequestCount();
        }
        $this->requestExecutor->execute($command, $this->sessionInfo);
        $operation->setResult($command->getResult());
        return $operation->getRevisionFromResult($className);
    }

    public function getCountFor(?string $id): int
    {
        $operation = new GetRevisionsCountOperation($id);
        $command = $operation->createRequest();
        if ($this->sessionInfo != null) {
            $this->sessionInfo->incrementRequestCount();
        }
        $this->requestExecutor->execute($command, $this->sessionInfo);
        return $command->getResult();
    }
}

<?php

namespace RavenDB\Documents\Session\Operations;

use DateTime;
use RavenDB\Constants\DocumentsMetadata;
use RavenDB\Documents\Commands\GetRevisionsCommand;
use RavenDB\Documents\Session\DocumentInfo;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Json\JsonArrayResult;
use RavenDB\Json\MetadataAsDictionary;
use RavenDB\Type\ExtendedArrayObject;
use RavenDB\Type\StringArray;

class GetRevisionOperation
{
    private ?InMemoryDocumentSessionOperations $session = null;

    private ?JsonArrayResult $result = null;
    private ?GetRevisionsCommand $command = null;

    protected function __construct(?InMemoryDocumentSessionOperations $session)
    {
        if ($session == null) {
            throw new IllegalArgumentException("Session cannot be null");
        }

        $this->session = $session;
    }

    public static function withPagination(?InMemoryDocumentSessionOperations $session, ?string $id, int $start, int $pageSize, bool $metadataOnly = false): self
    {
        if ($id == null) {
            throw new IllegalArgumentException("Id cannot be null");
        }

        $operation = new self($session);
        $operation->command = GetRevisionsCommand::withPagination($id, $start, $pageSize, $metadataOnly);

        return $operation;
    }

    public static function beforeDate(?InMemoryDocumentSessionOperations $session, ?string $id, ?DateTime $before): self
    {
        $operation = new self($session);
        $operation->command = GetRevisionsCommand::beforeDate($id, $before);
        return $operation;
    }

    public static function forChangeVector(?InMemoryDocumentSessionOperations $session, ?string $changeVector): self
    {
        $operation = new self($session);
        $operation->command = GetRevisionsCommand::forChangeVector($changeVector);
        return $operation;
    }

    public static function forChangeVectors(?InMemoryDocumentSessionOperations $session, StringArray|array $changeVectors): self
    {
        $operation = new self($session);
        $operation->command = GetRevisionsCommand::forChangeVectors($changeVectors);
        return $operation;
    }

    public function createRequest(): GetRevisionsCommand
    {
        $this->session->incrementRequestCount();
        return $this->command;
    }

    public function setResult(null|array|JsonArrayResult $result)
    {
        if (is_array($result)) {
            $this->result = new JsonArrayResult();
            $this->result->setResults($result['Results']);
            return;
        }

        $this->result = $result;
    }

    public function getCommand(): GetRevisionsCommand
    {
        return $this->command;
    }

    public function getRevisionFromResult(?string $className): ?object
    {
        if ($this->result == null) {
            return null;
        }

        if (count($this->result->getResults()) == 0) {
            return null;
        }

        return $this->getRevision($className, $this->result->getResults()[0]);
    }

    public function getRevision(?string $className, ?array $document = null): ?object
    {
        if ($document == null) {
            return null;
        }

        $metadata = null;
        $id = null;
        if (array_key_exists(DocumentsMetadata::KEY, $document)) {
            $metadata = $document[DocumentsMetadata::KEY];
            $idNode = $metadata[DocumentsMetadata::ID];
            if ($idNode != null) {
                $id = strval($idNode);
            }
        }

        $changeVector = null;
        if ($metadata != null && array_key_exists(DocumentsMetadata::CHANGE_VECTOR, $metadata)) {
            $changeVectorNode = $metadata[DocumentsMetadata::CHANGE_VECTOR];
            if ($changeVectorNode != null) {
                $changeVector = strval($changeVectorNode);
            }
        }

        $entity = $this->session->getEntityToJson()->convertToEntity($className, $id, $document, !$this->session->noTracking);
        $documentInfo = new DocumentInfo();
        $documentInfo->setId($id);
        $documentInfo->setChangeVector($changeVector);
        $documentInfo->setDocument($document);
        $documentInfo->setMetadata($metadata);
        $documentInfo->setEntity($entity);
        $this->session->documentsByEntity->put($entity, $documentInfo);

        return $entity;
    }

    public function getRevisionsFor(?string $className): array
    {
        $resultsCount = count($this->result->getResults());
        $results = [];
        for ($i = 0; $i < $resultsCount; $i++) {
            $document = $this->result->getResults()[$i];
            $results[] = $this->getRevision($className, $document);
        }

        return $results;
    }

    public function getRevisionsMetadataFor(): array
    {
        $resultsCount = count($this->result->getResults());
        $results = [];
        for ($i = 0; $i < $resultsCount; $i++) {
            $document = $this->result->getResults()[$i];

            $metadata = null;
            if (array_key_exists(DocumentsMetadata::KEY, $document)) {
                $metadata = $document[DocumentsMetadata::KEY];
            }

            $results[] = new MetadataAsDictionary($metadata);
        }

        return $results;
    }

    public function getRevisions(?string $className): array
    {
        $results = new ExtendedArrayObject();
        $results->setKeysCaseInsensitive(true);

        $i = 0;
        foreach ($this->command->getChangeVectors() as $changeVector) {
            if ($changeVector == null) {
                $i++;
                continue;
            }
            $jsonNode = $this->result->getResults()[$i];
            $objectNode = empty($jsonNode) ? null : $jsonNode;
            $results[$changeVector] = $this->getRevision($className, $objectNode);
            $i++;
        }

        return $results->getArrayCopy();
    }
}

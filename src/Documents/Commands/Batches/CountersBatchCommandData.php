<?php

namespace RavenDB\Documents\Commands\Batches;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\Counters\CounterOperation;
use RavenDB\Documents\Operations\Counters\CounterOperationList;
use RavenDB\Documents\Operations\Counters\CounterOperationType;
use RavenDB\Documents\Operations\Counters\DocumentCountersOperation;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Utils\StringUtils;

class CountersBatchCommandData implements CommandDataInterface
{
    private ?string $id = null;
    private ?string $name = null;
    private ?string $changeVector = null;

    private bool $fromEtl = false;
    private ?DocumentCountersOperation $counters = null;

    public function __construct(?string $documentId, null|CounterOperation|CounterOperationList $counterOperations)
    {
        if (StringUtils::isBlank($documentId)) {
            throw new IllegalArgumentException("DocumentId cannot be null");
        }

        if ($counterOperations == null) {
            throw new IllegalArgumentException("CounterOperation cannot be null");
        }


        $this->id = $documentId;
        $this->name = null;
        $this->changeVector = null;

        if ($counterOperations instanceof CounterOperation) {
            $counterOperations = CounterOperationList::fromArray([$counterOperations]);
        }

        $this->counters = new DocumentCountersOperation();
        $this->counters->setDocumentId($documentId);
        $this->counters->setOperations($counterOperations);
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getChangeVector(): ?string
    {
        return $this->changeVector;
    }

    public function getType(): ?CommandType
    {
        return CommandType::counters();
    }

    public function isFromEtl(): bool
    {
        return $this->fromEtl;
    }

    public function getCounters(): ?DocumentCountersOperation
    {
        return $this->counters;
    }

    public function hasDelete(?string $counterName): bool
    {
        return $this->hasOperationType(CounterOperationType::delete(), $counterName);
    }

    public function hasIncrement(?string $counterName): bool
    {
        return $this->hasOperationType(CounterOperationType::increment(), $counterName);
    }

    private function hasOperationType(CounterOperationType $type, ?string $counterName): bool
    {
        /** @var CounterOperation $op */
        foreach ($this->counters->getOperations() as $op) {
            if ($counterName != $op->getCounterName()) {
                continue;
            }

            if ($op->getType()->equals($type)) {
                return true;
            }
        }

        return false;
    }

    public function serialize(?DocumentConventions $conventions): array
    {
        $data = [];

        $data["Id"] = $this->id;
        $data["Counters"] = $this->counters?->serialize();
        $data["Type"] = "Counters";

        if ($this->fromEtl != null) {
            $data["FromEtl"] = $this->fromEtl;
        }
        return $data;
    }

    public function onBeforeSaveChanges(?InMemoryDocumentSessionOperations $session): void
    {
        // TODO: Implement onBeforeSaveChanges() method.
    }
}

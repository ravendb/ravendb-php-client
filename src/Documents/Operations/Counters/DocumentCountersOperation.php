<?php

namespace RavenDB\Documents\Operations\Counters;

use Symfony\Component\Serializer\Annotation\SerializedName;

class DocumentCountersOperation
{
    #[SerializedName('Operations')]
    private ?CounterOperationList $operations = null;
    #[SerializedName('DocumentId')]
    private ?string $documentId = null;

    public function & getOperations(): ?CounterOperationList
    {
        return $this->operations;
    }

    public function setOperations(null|CounterOperationList|array $operations): void
    {
        $this->operations = is_array($operations) ? CounterOperationList::fromArray($operations) : $operations;
    }

    public function getDocumentId(): ?string
    {
        return $this->documentId;
    }

    public function setDocumentId(?string $documentId): void
    {
        $this->documentId = $documentId;
    }

    public function serialize(): array
    {
        $data = [];

        $data["DocumentId"] = $this->documentId;

        $data["Operations"] = [];

        /** @var CounterOperation $operation */
        foreach ($this->operations as $operation) {
            $data["Operations"][] = $operation->serialize();
        }

        return $data;
    }
}

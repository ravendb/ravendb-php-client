<?php

namespace RavenDB\Documents\Commands\Batches;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;

class DeleteCompareExchangeCommandData implements CommandDataInterface
{
    public ?int $index;

    private ?string $id = null;
    private ?string $name = null;
    private ?string $changeVector = null;

    public function __construct(?string $key, ?int $index) {
        $this->id = $key;
        $this->index = $index;
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

    public function getType(): CommandType
    {
        return CommandType::compareExchangeDelete();
    }

    public function serialize(?DocumentConventions $conventions): array
    {
        return [
            'Id' => $this->id,
            'Index' => $this->index,
            'Type' => CommandType::COMPARE_EXCHANGE_DELETE
        ];
    }

    public function onBeforeSaveChanges(?InMemoryDocumentSessionOperations $session): void
    {
        // Leave empty
    }
}

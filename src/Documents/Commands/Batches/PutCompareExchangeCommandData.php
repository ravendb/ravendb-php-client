<?php

namespace RavenDB\Documents\Commands\Batches;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;

class PutCompareExchangeCommandData implements CommandDataInterface
{
    private ?int $index = null;
    private ?array $document = null;

    private ?string $id = null;
    private ?string $name = null;
    private ?string $changeVector = null;

    public function __construct(?string $key, array $value, int $index)
    {
        $this->id       = $key;
        $this->document = $value;
        $this->index    = $index;
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
        return CommandType::compareExchangePut();
    }

    public function serialize(?DocumentConventions $conventions): array
    {
        return [
            'Id'       => $this->id,
            "Document" => $this->document,
            "Index"    => $this->index,
            "Type"     => CommandType::COMPARE_EXCHANGE_PUT,
        ];
    }

    public function onBeforeSaveChanges(?InMemoryDocumentSessionOperations $session): void
    {
    }
}

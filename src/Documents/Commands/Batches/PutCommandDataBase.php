<?php

namespace RavenDB\Documents\Commands\Batches;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Session\ForceRevisionStrategy;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Exceptions\IllegalArgumentException;

class PutCommandDataBase implements CommandDataInterface
{
    private string $id;
    private ?string $name = null;
    private ?string $changeVector = null;
    private ?string $originalChangeVector = null;
    private array $document;
    private CommandType $type;
    private ForceRevisionStrategy $forceRevisionCreationStrategy;

    protected function __construct(
        string $id,
        ?string $changeVector,
        ?string $originalChangeVector,
        array $document,
        ?ForceRevisionStrategy $strategy = null
    ) {
        if (!count($document)) {
            throw new IllegalArgumentException("Document cannot be null");
        }

        $this->id = $id;
        $this->changeVector = $changeVector;
        $this->originalChangeVector = $originalChangeVector;
        $this->document = $document;
        $this->forceRevisionCreationStrategy = $strategy ?? ForceRevisionStrategy::None();

        $this->type = CommandType::put();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getChangeVector(): ?string
    {
        return $this->changeVector;
    }

    public function getOriginalChangeVector(): ?string
    {
        return $this->originalChangeVector;
    }

    public function getDocument(): array {
        return $this->document;
    }

    public function getType(): CommandType
    {
        return $this->type;
    }

    public function getForceRevisionCreationStrategy(): ForceRevisionStrategy
    {
        return $this->forceRevisionCreationStrategy;
    }

//    public void serialize(JsonGenerator generator, DocumentConventions conventions) throws IOException {
//        generator.writeStartObject();
//        generator.writeStringField("Id", id);
//        generator.writeStringField("ChangeVector", changeVector);
//        if (originalChangeVector != null) {
//            generator.writeStringField("OriginalChangeVector", originalChangeVector);
//        }
//
//        generator.writeFieldName("Document");
//        generator.writeTree(document);
//
//        generator.writeStringField("Type", "PUT");
//
//        if (forceRevisionCreationStrategy != ForceRevisionStrategy.NONE) {
//            generator.writeStringField("ForceRevisionCreationStrategy", SharpEnum.value(forceRevisionCreationStrategy));
//        }
//        generator.writeEndObject();
//    }

    public function serialize(?DocumentConventions $conventions): array
    {
        $data = [
            'Id' => $this->id,
            'ChangeVector' => $this->changeVector,
            'Type' => 'PUT'
        ];

        if (!empty($this->originalChangeVector)) {
            $data['OriginalChangeVector'] = $this->originalChangeVector;
        }

        if (!$this->forceRevisionCreationStrategy->isNone()) {
            $data['ForceRevisionCreationStrategy'] = $this->forceRevisionCreationStrategy->getValue();
        }

        $data['Document'] = $this->document;

        return $data;
    }

    public function onBeforeSaveChanges(?InMemoryDocumentSessionOperations $session): void
    {
        // TODO: Implement onBeforeSaveChanges() method.
    }
}

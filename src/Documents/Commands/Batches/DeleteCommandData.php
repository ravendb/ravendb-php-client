<?php

namespace RavenDB\Documents\Commands\Batches;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Session\BeforeDeleteEventArgs;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Exceptions\IllegalArgumentException;

class DeleteCommandData implements CommandDataInterface
{
    private string $id;
    private string $name;
    private string $changeVector;
    private CommandType $type;
    private string $originalChangeVector;
    private array $document;

    public function __construct(string $id, string $changeVector, string $originalChangeVector = null)
    {
        if ($id == null) {
            throw new IllegalArgumentException("Id cannot be null");
        }

        $this->id = $id;
        $this->changeVector = $changeVector;
        $this->originalChangeVector = $originalChangeVector;

        $this->type = CommandType::delete();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getChangeVector(): string
    {
        return $this->changeVector;
    }

    public function getOriginalChangeVector(): string
    {
        return $this->originalChangeVector;
    }

    public function getType(): CommandType
    {
        return $this->type;
    }

    public function getDocument(): array {
        return $this->document;
    }

    public function setDocument(array $document): void {
        $this->document = $document;
    }

    public function serialize(DocumentConventions $conventions): array
    {
        // @todo: implement this serialize
    }

//    public void serialize(JsonGenerator generator, DocumentConventions conventions) throws IOException {
//        generator.writeStartObject();
//
//        generator.writeStringField("Id", id);
//        generator.writeStringField("ChangeVector", changeVector);
//        generator.writeObjectField("Type", "DELETE");
//        generator.writeObjectField("Document", document);
//
//        if (originalChangeVector != null) {
//            generator.writeStringField("OriginalChangeVector", originalChangeVector);
//        }
//
//        serializeExtraFields(generator);
//
//        generator.writeEndObject();
//    }

//    protected function serializeExtraFields(JsonGenerator generator): void throws IOException
//    {
//         empty by design
//    }

    public function onBeforeSaveChanges(InMemoryDocumentSessionOperations $session): void
    {
        $session->onBeforeDeleteInvoke(new BeforeDeleteEventArgs($session, $this->id, null));
    }
}

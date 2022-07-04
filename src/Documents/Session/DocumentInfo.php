<?php

namespace RavenDB\Documents\Session;

use RavenDB\Constants\DocumentsMetadata;
use RavenDB\Exceptions\IllegalStateException;

class DocumentInfo
{
    private string $id;

    private ?string $changeVector;
    private ConcurrencyCheckMode $concurrencyCheckMode;

    private bool $ignoreChanges = false;

    private ?array $metadata = null;
    private ?array $document = null;

    private ?MetadataDictionaryInterface $metadataInstance = null;

    private ?object $entity;
    private bool $newDocument = false;
    private string $collection = '';

    public function __construct()
    {
        $this->concurrencyCheckMode = ConcurrencyCheckMode::auto();
    }

    /**
     * @throws IllegalStateException
     */
    public static function getNewDocumentInfo(array $document): DocumentInfo
    {
        if (!array_key_exists('@metadata', $document)) {
            throw new IllegalStateException("Document must have a metadata");
        }

        $metadata = $document['@metadata']; // todo: update from constant Constants.Documents.Metadata.KEY

        if (!array_key_exists(DocumentsMetadata::ID, $metadata)) {
            throw new IllegalStateException("Document must have an id");
        }
        $id = $metadata[DocumentsMetadata::ID];

        if (!array_key_exists(DocumentsMetadata::CHANGE_VECTOR, $metadata)) {
            throw new IllegalStateException("Document " . $id . " must have a Change Vector");
        }
        $changeVector = $metadata[DocumentsMetadata::CHANGE_VECTOR];

        $newDocumentInfo = new DocumentInfo();
        $newDocumentInfo->setId($id);
        $newDocumentInfo->setDocument($document);
        $newDocumentInfo->setMetadata($metadata);
        $newDocumentInfo->setEntity(null);
        $newDocumentInfo->setChangeVector($changeVector);
        return $newDocumentInfo;
    }


    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getChangeVector(): ?string
    {
        return $this->changeVector;
    }

    public function setChangeVector(?string $changeVector): void
    {
        $this->changeVector = $changeVector;
    }

    public function getConcurrencyCheckMode(): ConcurrencyCheckMode
    {
        return $this->concurrencyCheckMode;
    }

    public function setConcurrencyCheckMode(ConcurrencyCheckMode $concurrencyCheckMode): void
    {
        $this->concurrencyCheckMode = $concurrencyCheckMode;
    }

    public function isIgnoreChanges(): bool
    {
        return $this->ignoreChanges;
    }

    public function setIgnoreChanges(bool $ignoreChanges): void
    {
        $this->ignoreChanges = $ignoreChanges;
    }

    public function isNewDocument(): bool
    {
        return $this->newDocument;
    }

    public function setNewDocument(bool $newDocument): void
    {
        $this->newDocument = $newDocument;
    }

    public function &getCollection(): string
    {
        return $this->collection;
    }

    public function setCollection(string $collection): void
    {
        $this->collection = $collection;
    }

    public function & getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function &getDocument(): array
    {
        return $this->document;
    }

    public function setDocument(array $document): void
    {
        $this->document = $document;
    }

    public function &getEntity(): ?object
    {
        return $this->entity;
    }

    public function setEntity(?object $entity): void
    {
        $this->entity = $entity;
    }

    public function & getMetadataInstance(): ?MetadataDictionaryInterface
    {
        return $this->metadataInstance;
    }

    public function setMetadataInstance(?MetadataDictionaryInterface $metadataInstance): void
    {
        $this->metadataInstance = $metadataInstance;
    }
}

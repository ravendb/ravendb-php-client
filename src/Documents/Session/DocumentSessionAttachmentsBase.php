<?php

namespace RavenDB\Documents\Session;

use RavenDB\Constants\DocumentsMetadata;
use RavenDB\Documents\Commands\Batches\CommandType;
use RavenDB\Documents\Commands\Batches\CopyAttachmentCommandData;
use RavenDB\Documents\Commands\Batches\DeleteAttachmentCommandData;
use RavenDB\Documents\Commands\Batches\MoveAttachmentCommandData;
use RavenDB\Documents\Commands\Batches\PutAttachmentCommandData;
use RavenDB\Documents\Operations\Attachments\AttachmentName;
use RavenDB\Documents\Operations\Attachments\AttachmentNameArray;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Extensions\JsonExtensions;
use RavenDB\Utils\StringUtils;
use Symfony\Component\Mime\Part\DataPart;

class DocumentSessionAttachmentsBase extends AdvancedSessionExtensionBase
{
    protected function __construct(?InMemoryDocumentSessionOperations $session)
    {
        parent::__construct($session);
    }

    function getNames(?object $entity): AttachmentNameArray
    {
        if ($entity == null) {
            return new AttachmentNameArray();
        }

        $document = $this->session->documentsByEntity->get($entity);
        if ($document == null) {
            $this->throwEntityNotInSession($entity);
        }

        $attachments = null;
        if (array_key_exists(DocumentsMetadata::ATTACHMENTS, $document->getMetadata())) {
            $attachments = $document->getMetadata()[DocumentsMetadata::ATTACHMENTS];
        };
        if ($attachments == null) {
            return new AttachmentNameArray();
        }

        $results = new AttachmentNameArray();
        foreach ($attachments as $attachmentNameData) {
            $attachmentName = JsonExtensions::getDefaultMapper()->denormalize($attachmentNameData, AttachmentName::class);
            $results->append($attachmentName);
        }
        return $results;
    }

    public function storeFile(object|string|null $idOrEntity, ?string $name, string $filePath): void
    {
        $this->store($idOrEntity, $name, DataPart::fromPath($filePath), mime_content_type($filePath));
    }

    public function store(object|string|null $idOrEntity, ?string $name, mixed $stream, ?string $contentType = null): void
    {
        if (is_object($idOrEntity)) {
            $this->storeByEntity($idOrEntity, $name, $stream, $contentType);
            return;
        }
        if (is_string($idOrEntity)) {
            $this->storeById($idOrEntity, $name, $stream, $contentType);
            return;
        }
        throw new IllegalArgumentException('Wrong argument type');
    }

    protected function storeByEntity(?object $entity, ?string $name, mixed $stream, ?string $contentType = null): void
    {
        $document = $this->session->documentsByEntity->get($entity);
        if ($document == null) {
            $this->throwEntityNotInSessionOrMissingId($entity);
        }
        $this->storeById($document->getId(), $name, $stream, $contentType);
    }

    protected function storeById(?string $documentId, ?string $name, mixed $stream, ?string $contentType = null): void
    {
        if (StringUtils::isBlank($documentId)) {
            throw new IllegalArgumentException("DocumentId cannot be null");
        }

        if (StringUtils::isBlank($name)) {
            throw new IllegalArgumentException("Name cannot be null");
        }

        if ($this->deferredCommandsMap->hasKeyWith($documentId, CommandType::delete(), null)) {
            $this->throwOtherDeferredCommandException($documentId, $name, "store", "delete");
        }

        if ($this->deferredCommandsMap->hasKeyWith($documentId, CommandType::attachmentPut(), $name)) {
            $this->throwOtherDeferredCommandException($documentId, $name, "store", "create");
        }

        if ($this->deferredCommandsMap->hasKeyWith($documentId, CommandType::attachmentDelete(), $name)) {
            $this->throwOtherDeferredCommandException($documentId, $name, "store", "delete");
        }

        if ($this->deferredCommandsMap->hasKeyWith($documentId, CommandType::attachmentMove(), $name)) {
            $this->throwOtherDeferredCommandException($documentId, $name, "store", "rename");
        }

        $documentInfo = null;
        if ($this->documentsById->offsetExists($documentId)) {
            $documentInfo = $this->documentsById->offsetGet($documentId);
        }
        if ($documentInfo != null && $this->session->deletedEntities->contains($documentInfo->getEntity())) {
            $this->throwDocumentAlreadyDeleted($documentId, $name, "store", null, $documentId);
        }

        $this->defer(new PutAttachmentCommandData($documentId, $name, $stream, $contentType, null));
    }


    protected function throwEntityNotInSessionOrMissingId(?object $entity): void
    {
        throw new IllegalArgumentException($entity . " is not associated with the session. Use documentId instead or track the entity in the session.");
    }

    protected function throwEntityNotInSession(?object $entity): void
    {
        throw new IllegalArgumentException($entity . " is not associated with the session. You need to track the entity in the session.");
    }

    public function delete(object|string|null $idOrEntity, ?string $name): void
    {
        if (is_object($idOrEntity)) {
            $this->deleteByEntity($idOrEntity, $name);
            return;
        }

        if (is_string($idOrEntity)) {
            $this->deleteById($idOrEntity, $name);
            return;
        }

        throw new IllegalArgumentException('Wrong argument type');
    }

    protected function deleteByEntity(?object $entity, ?string $name): void
    {
        $document = $this->session->documentsByEntity->get($entity);
        if ($document == null) {
            $this->throwEntityNotInSessionOrMissingId($entity);
        }

        $this->deleteById($document->getId(), $name);
    }

    private function deleteById(?string $documentId, ?string $name): void
    {
        if (StringUtils::isBlank($documentId)) {
            throw new IllegalArgumentException("DocumentId cannot be null");
        }

        if (StringUtils::isBlank($name)) {
            throw new IllegalArgumentException("Name cannot be null");
        }

        if ($this->deferredCommandsMap->hasKeyWith($documentId, CommandType::delete(), null) ||
                $this->deferredCommandsMap->hasKeyWith($documentId, CommandType::attachmentDelete(), $name)) {
            return; // no-op
        }

        $documentInfo = $this->documentsById->getValue($documentId);
        if ($documentInfo != null && $this->session->deletedEntities->contains($documentInfo->getEntity())) {
            return;  //no-op
        }

        if ($this->deferredCommandsMap->hasKeyWith($documentId, CommandType::attachmentPut(), $name)) {
            $this->throwOtherDeferredCommandException($documentId, $name, "delete", "create");
        }

        if ($this->deferredCommandsMap->hasKeyWith($documentId, CommandType::attachmentMove(), $name)) {
            $this->throwOtherDeferredCommandException($documentId, $name, "delete", "rename");
        }

        $this->defer(new DeleteAttachmentCommandData($documentId, $name, null));
    }

    public function rename(string|object|null $idOrEntity, ?string $name, ?string $newName): void
    {
        $id = is_object($idOrEntity) ? $this->getEntityId($idOrEntity) : $idOrEntity;
        $this->move($id, $name, $id, $newName);
    }

    public function move(object|string|null $sourceIdOrEntity, ?string $sourceName, object|string|null $destinationIdOrEntity, ?string $destinationName): void
    {
        $sourceId = is_object($sourceIdOrEntity) ? $this->getEntityId($sourceIdOrEntity) : $sourceIdOrEntity;
        $destinationId = is_object($destinationIdOrEntity) ? $this->getEntityId($destinationIdOrEntity) : $destinationIdOrEntity;

        if (!is_string($sourceId) || !is_string($destinationId)) {
            throw new IllegalArgumentException('Wrong argument type');
        }

        $this->moveById($sourceId, $sourceName, $destinationId, $destinationName);
    }

    protected function moveById(?string $sourceDocumentId, ?string $sourceName, ?string $destinationDocumentId, ?string $destinationName): void
    {
        if (StringUtils::isBlank($sourceDocumentId)) {
            throw new IllegalArgumentException("SourceDocumentId is required");
        }

        if (StringUtils::isBlank($sourceName)) {
            throw new IllegalArgumentException("SourceName is required");
        }

        if (StringUtils::isBlank($destinationDocumentId)) {
            throw new IllegalArgumentException("DestinationDocumentId is required");
        }

        if (StringUtils::isBlank($destinationName)) {
            throw new IllegalArgumentException("DestinationName is required");
        }

        if ((strcasecmp($sourceDocumentId, $destinationDocumentId) == 0) && (strcasecmp($sourceName, $destinationName) == 0)) {
            return; // no-op
        }

        $sourceDocument = $this->documentsById->getValue($sourceDocumentId);
        if ($sourceDocument != null && $this->session->deletedEntities->contains($sourceDocument->getEntity())) {
            $this->throwDocumentAlreadyDeleted($sourceDocumentId, $sourceName, "move", $destinationDocumentId, $sourceDocumentId);
        }

        $destinationDocument = $this->documentsById->getValue($destinationDocumentId);
        if ($destinationDocument != null && $this->session->deletedEntities->contains($destinationDocument->getEntity())) {
            $this->throwDocumentAlreadyDeleted($sourceDocumentId, $sourceName, "move", $destinationDocumentId, $destinationDocumentId);
        }

        if ($this->deferredCommandsMap->hasKeyWith($sourceDocumentId, CommandType::attachmentDelete(), $sourceName)) {
            $this->throwOtherDeferredCommandException($sourceDocumentId, $sourceName, "rename", "delete");
        }

        if ($this->deferredCommandsMap->hasKeyWith($sourceDocumentId, CommandType::attachmentMove(), $sourceName)) {
            $this->throwOtherDeferredCommandException($sourceDocumentId, $sourceName, "rename", "rename");
        }

        if ($this->deferredCommandsMap->hasKeyWith($destinationDocumentId, CommandType::attachmentDelete(), $destinationName)) {
            $this->throwOtherDeferredCommandException($destinationDocumentId, $destinationName, "rename", "delete");
        }

        if ($this->deferredCommandsMap->hasKeyWith($destinationDocumentId, CommandType::attachmentMove(), $destinationName)) {
            $this->throwOtherDeferredCommandException($destinationDocumentId, $destinationName, "rename", "rename");
        }

        $this->defer(new MoveAttachmentCommandData($sourceDocumentId, $sourceName, $destinationDocumentId, $destinationName, null));
    }

    public function copy(object|string|null $sourceIdOrEntity, ?string $sourceName, object|string|null $destinationIdOrEntity, ?string $destinationName): void
    {
        $sourceId = is_object($sourceIdOrEntity) ? $this->getEntityId($sourceIdOrEntity) : $sourceIdOrEntity;
        $destinationId = is_object($destinationIdOrEntity) ? $this->getEntityId($destinationIdOrEntity) : $destinationIdOrEntity;

        if (!is_string($sourceId) || !is_string($destinationId)) {
            throw new IllegalArgumentException('Wrong argument type');
        }

        $this->copyById($sourceId, $sourceName, $destinationId, $destinationName);
    }

    private function getEntityId(object $entity): string
    {
        $document = $this->session->documentsByEntity->get($entity);
        if ($document == null) {
            $this->throwEntityNotInSessionOrMissingId($entity);
        }
        return $document->getId();
    }

    protected function copyById(?string $sourceDocumentId, ?string $sourceName, ?string $destinationDocumentId, ?string $destinationName): void
    {
        if (StringUtils::isBlank($sourceDocumentId)) {
            throw new IllegalArgumentException("SourceDocumentId is required");
        }

        if (StringUtils::isBlank($sourceName)) {
            throw new IllegalArgumentException("SourceName is required");
        }

        if (StringUtils::isBlank($destinationDocumentId)) {
            throw new IllegalArgumentException("DestinationDocumentId is required");
        }

        if (StringUtils::isBlank($destinationName)) {
            throw new IllegalArgumentException("DestinationName is required");
        }

        if ((strcasecmp($sourceDocumentId, $destinationDocumentId) == 0) && (strcasecmp($sourceName, $destinationName) == 0)) {
            return; // no-op
        }

        $sourceDocument = $this->documentsById->getValue($sourceDocumentId);
        if ($sourceDocument != null && $this->session->deletedEntities->contains($sourceDocument->getEntity())) {
            $this->throwDocumentAlreadyDeleted($sourceDocumentId, $sourceName, "copy", $destinationDocumentId, $sourceDocumentId);
        }

        $destinationDocument = $this->documentsById->getValue($destinationDocumentId);
        if ($destinationDocument != null && $this->session->deletedEntities->contains($destinationDocument->getEntity())) {
            $this->throwDocumentAlreadyDeleted($sourceDocumentId, $sourceName, "copy", $destinationDocumentId, $destinationDocumentId);
        }

        if ($this->deferredCommandsMap->hasKeyWith($sourceDocumentId, CommandType::attachmentDelete(), $sourceName)) {
            $this->throwOtherDeferredCommandException($sourceDocumentId, $sourceName, "copy", "delete");
        }

        if ($this->deferredCommandsMap->hasKeyWith($sourceDocumentId, CommandType::attachmentMove(), $sourceName)) {
            $this->throwOtherDeferredCommandException($sourceDocumentId, $sourceName, "copy", "rename");
        }

        if ($this->deferredCommandsMap->hasKeyWith($destinationDocumentId, CommandType::attachmentDelete(), $destinationName)) {
            $this->throwOtherDeferredCommandException($destinationDocumentId, $destinationName, "copy", "delete");
        }

        if ($this->deferredCommandsMap->hasKeyWith($destinationDocumentId, CommandType::attachmentMove(), $destinationName)) {
            $this->throwOtherDeferredCommandException($destinationDocumentId, $destinationName, "copy", "rename");
        }

        $this->defer(new CopyAttachmentCommandData($sourceDocumentId, $sourceName, $destinationDocumentId, $destinationName, null));
    }

    private static function throwDocumentAlreadyDeleted(?string $documentId, ?string $name, ?string $operation, ?string $destinationDocumentId, ?string $deletedDocumentId): void
    {
        throw new IllegalStateException("Can't " . $operation . " attachment '" . $name . "' of document '" . $documentId . "' " .
                ($destinationDocumentId != null ? " to '" . $destinationDocumentId . "'" : "") .
                ", the document '" . $deletedDocumentId . "' was already deleted in this session");
    }

    private static function throwOtherDeferredCommandException(?string $documentId, ?string $name, ?string $operation, ?string $previousOperation): void
    {
        throw new IllegalStateException("Can't " . $operation . " attachment '" . $name . "' of document '"
                . $documentId . "', there is a deferred command registered to " . $previousOperation . " an attachment with '" . $name . "' name.");
    }
}

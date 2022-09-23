<?php

namespace RavenDB\Documents\Session;

use RavenDB\Documents\Operations\Attachments\AttachmentNameArray;

interface AttachmentsSessionOperationsBaseInterface
{
    /**
     * Returns the attachment's info of a document.
     * @param ?object $entity Entity to use
     * @return AttachmentNameArray attachments names
     */
    function getNames(?object $entity): AttachmentNameArray;

    /**
     * Stores attachment to be sent in the session.
     * @param object|string|null $idOrEntity Entity or Document Id
     * @param string|null $name Name of attachment
     * @param mixed $stream Attachment stream
     * @param string|null $contentType Content type
     */
    public function store($idOrEntity, ?string $name, $stream, ?string $contentType = null): void;

    /**
     * Stores attachment to be sent in the session.
     * @param object|string|null $idOrEntity
     * @param string|null $name
     * @param string $filePath
     */
    public function storeFile($idOrEntity, ?string $name, string $filePath): void;

    /**
     * Marks the specified document's attachment for deletion. The attachment will be deleted when
     * saveChanges is called.
     * @param object|string|null $idOrEntity
     * @param string|null $name
     */
    public function delete($idOrEntity, ?string $name): void;

    /**
     * Marks the specified document's attachment for rename. The attachment will be renamed when saveChanges is called.
     * @param string|object|null $idOrEntity document which holds the attachment
     * @param string|null $name the attachment name
     * @param string|null $newName the attachment new name
     */
    public function rename($idOrEntity, ?string $name, ?string $newName): void;

    /**
     * Copies specified source document attachment to destination document. The operation will be executed when saveChanges is called.
     *
     * @param object|string|null $sourceIdOrEntity the document which holds the attachment
     * @param string|null $sourceName the attachment name
     * @param object|string|null $destinationIdOrEntity the document to which the attachment will be copied
     * @param string|null $destinationName the attachment name
     */
    public function copy($sourceIdOrEntity, ?string $sourceName, $destinationIdOrEntity, ?string $destinationName): void;

    /**
     * Moves specified source document attachment to destination document. The operation will be executed when saveChanges is called.
     *
     * @param object|string|null $sourceIdOrEntity the document which holds the attachment
     * @param string|null $sourceName the attachment name
     * @param object|string|null $destinationIdOrEntity the document to which the attachment will be moved
     * @param string|null $destinationName the attachment name
     */
    public function move($sourceIdOrEntity, ?string $sourceName, $destinationIdOrEntity, ?string $destinationName): void;
}

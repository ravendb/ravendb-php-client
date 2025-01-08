<?php

namespace RavenDB\Documents\Session;

use RavenDB\Documents\Operations\Attachments\CloseableAttachmentResult;

interface AttachmentsSessionOperationsInterface extends AttachmentsSessionOperationsBaseInterface
{
    /**
     * Check if attachment exists
     * @param ?string $documentId Document Id
     * @param ?string $name Attachment name
     * @return bool true, if attachment exists
     */
    function exists(?string $documentId, ?string $name): bool;


    /**
     * Returns the attachment by the document id and attachment name.
     *
     * @param object|string|null $idOrEntity
     * @param string|null $name
     * @return CloseableAttachmentResult
     */
    public function get($idOrEntity, ?string $name): CloseableAttachmentResult;

//    /**
//     * Returns enumerator of attachment name and stream.
//     * @param attachments Attachments to get
//     * @return attachments
//     */
//    CloseableAttachmentsResult get(List<AttachmentRequest> attachments);

    /**
     * Returns the revision attachment by the document id and attachment name.
     *
     * @param ?string $documentId Document Id
     * @param ?string $name Name of attachment
     * @param ?string $changeVector Change vector
     * @return CloseableAttachmentResult;
     */
    public function getRevision(?string $documentId, ?string $name, ?string $changeVector): CloseableAttachmentResult;
}

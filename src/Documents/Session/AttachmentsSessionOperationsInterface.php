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
//
//    /**
//     * Returns the revision attachment by the document id and attachment name.
//     * @param documentId Document Id
//     * @param name Name of attachment
//     * @param changeVector Change vector
//     * @return Attachment
//     */
//    CloseableAttachmentResult getRevision(String documentId, String name, String changeVector);
}

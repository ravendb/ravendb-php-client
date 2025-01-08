<?php

namespace RavenDB\Documents\Session;

use RavenDB\Documents\Attachments\AttachmentType;
use RavenDB\Documents\Commands\HeadAttachmentCommand;
use RavenDB\Documents\Operations\Attachments\CloseableAttachmentResult;
use RavenDB\Documents\Operations\Attachments\GetAttachmentOperation;
use RavenDB\Exceptions\IllegalArgumentException;

class DocumentSessionAttachments extends DocumentSessionAttachmentsBase implements AttachmentsSessionOperationsInterface
{
    public function __construct(?InMemoryDocumentSessionOperations $session)
    {
        parent::__construct($session);
    }

    /**
     * Check if attachment exists
     * @param ?string $documentId Document Id
     * @param ?string $name Attachment name
     * @return bool true, if attachment exists
     */
    public function exists(?string $documentId, ?string $name): bool
    {
        $command = new HeadAttachmentCommand($documentId, $name, null);
        $this->session->incrementRequestCount();
        $this->requestExecutor->execute($command, $this->sessionInfo);
        return $command->getResult() != null;
    }


    /**
     * @param object|string|null $idOrEntity
     * @param string|null $name
     * @return CloseableAttachmentResult
     */
    public function get($idOrEntity, ?string $name): CloseableAttachmentResult
    {
        if (is_object($idOrEntity)) {
            return $this->getByEntity($idOrEntity, $name);
        }

        if (is_string($idOrEntity)) {
            return $this->getById($idOrEntity, $name);
        }

        throw new IllegalArgumentException('Wrong argument type');
    }

    /**
     * @param object|null $entity
     * @param string|null $name
     * @return CloseableAttachmentResult
     */
    protected function getByEntity(?object $entity, ?string $name): CloseableAttachmentResult
    {
        $document = $this->session->documentsByEntity->get($entity);
        if ($document == null) {
            $this->throwEntityNotInSessionOrMissingId($entity);
        }
        return $this->getById($document->getId(), $name);
    }

    /**
     * @param string|null $documentId
     * @param string|null $name
     * @return CloseableAttachmentResult
     */
    private function getById(?string $documentId, ?string $name): CloseableAttachmentResult
    {
        $operation = new GetAttachmentOperation($documentId, $name, AttachmentType::document(), null);
        $this->session->incrementRequestCount();
        return $this->session->getOperations()->send($operation, $this->sessionInfo);
    }

//    public CloseableAttachmentsResult get(List<AttachmentRequest> attachments) {
//        GetAttachmentsOperation operation = new GetAttachmentsOperation(attachments, AttachmentType.DOCUMENT);
//        return session.getOperations().send(operation, sessionInfo);
//    }

    public function getRevision(?string $documentId, ?string $name, ?string $changeVector): CloseableAttachmentResult
    {
        $operation = new GetAttachmentOperation($documentId, $name, AttachmentType::revision(), $changeVector);
        $this->session->incrementRequestCount();
        return $this->session->getOperations()->send($operation, $this->sessionInfo);
    }
}

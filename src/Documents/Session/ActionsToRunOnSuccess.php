<?php

namespace RavenDB\Documents\Session;

class ActionsToRunOnSuccess
{
    private InMemoryDocumentSessionOperations $session;
    private array $documentsByIdToRemove = [];
    private array $documentsByEntityToRemove = [];
    private array $documentInfosToUpdate = [];

    private bool $clearDeletedEntities = false;

    public function __construct(InMemoryDocumentSessionOperations $session)
    {
        $this->session = $session;
    }

    public function removeDocumentById(string $id): void
    {
        $this->documentsByIdToRemove[] = $id;
    }

    public function removeDocumentByEntity(object $entity): void
    {
        $this->documentsByEntityToRemove[] = $entity;
    }

    public function updateEntityDocumentInfo(DocumentInfo $documentInfo, array $document): void
    {
        $this->documentInfosToUpdate[] = array($documentInfo, $document);
    }

    public function clearSessionStateAfterSuccessfulSaveChanges(): void
    {
        /** @var string $id */
        foreach ($this->documentsByIdToRemove as $id) {
            unset($this->session->documentsById[$id]);
        }
         /** @var object $entity */
        foreach ($this->documentsByEntityToRemove as $entity) {
            $this->session->documentsByEntity->remove($entity);
        }

        foreach ($this->documentInfosToUpdate as $documentInfo) {
            $info = $documentInfo[0];
            $document = $documentInfo[1];
            $info->setNewDocument(false);
            $info->setDocument($document);
        }

        if ($this->clearDeletedEntities) {
            $this->session->deletedEntities->clear();
        }

        $this->session->deferredCommands = [];
        $this->session->deferredCommandsMap->clear();
    }

    public function clearDeletedEntities(): void
    {
        $this->clearDeletedEntities = true;
    }
}

<?php

namespace RavenDB\Documents\Session;

use RavenDB\Primitives\EventArgs;

class BeforeDeleteEventArgs extends EventArgs
{
    private MetadataDictionaryInterface $documentMetadata;

    private InMemoryDocumentSessionOperations $session;
    private string $documentId;
    private ?object $entity;

    public function __construct(InMemoryDocumentSessionOperations $session, string $documentId, ?object $entity)
    {
        $this->session = $session;
        $this->documentId = $documentId;
        $this->entity = $entity;
    }

    public function getSession(): InMemoryDocumentSessionOperations {
        return $this->session;
    }

    public function getDocumentId(): string
    {
        return $this->documentId;
    }

    public function getEntity(): ?object {
        return $this->entity;
    }

    public function getDocumentMetadata(): MetadataDictionaryInterface
    {
        if ($this->documentMetadata == null) {
            $this->documentMetadata = $this->session->getMetadataFor($$this->entity);
        }

        return $this->documentMetadata;
    }
}

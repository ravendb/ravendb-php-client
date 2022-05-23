<?php

namespace RavenDB\Documents\Session;

use RavenDB\Primitives\EventArgs;

class AfterConversionToEntityEventArgs extends EventArgs
{
    private ?InMemoryDocumentSessionOperations $session;
    private ?string $id;
    private ?object $entity;
    private array $document;

    public function __construct(?InMemoryDocumentSessionOperations $session, ?string $id, ?object $entity, array $document = [])
    {
        $this->id = $id;
        $this->entity = $entity;
        $this->document = $document;
        $this->session = $session;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getEntity(): ?object
    {
        return $this->entity;
    }

    public function getDocument(): array
    {
        return $this->document;
    }

    public function getSession(): ?InMemoryDocumentSessionOperations
    {
        return $this->session;
    }
}

<?php

namespace RavenDB\Documents\Session;

use RavenDB\Primitives\EventArgs;

class BeforeConversionToDocumentEventArgs extends EventArgs
{
    private string $id;
    private object $entity;
    private InMemoryDocumentSessionOperations $session;

    public function __construct(InMemoryDocumentSessionOperations $session, string $id, object $entity)
    {
        $this->session = $session;
        $this->id = $id;
        $this->entity = $entity;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEntity(): object
    {
        return $this->entity;
    }

    public function getSession(): InMemoryDocumentSessionOperations
    {
        return $this->session;
    }
}

<?php

namespace RavenDB\Documents\Session;

use RavenDB\Primitives\EventArgs;

class BeforeConversionToEntityEventArgs extends EventArgs
{
    private ?InMemoryDocumentSessionOperations $session = null;
    private ?string $id = null;
    private ?string $type = null;
    private array $document;

    public function __construct(?InMemoryDocumentSessionOperations $session, ?string $id, ?string $type, array $document = [])
    {
        $this->session = $session;
        $this->id = $id;
        $this->type = $type;
        $this->document = $document;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function & getDocument(): array
    {
        return $this->document;
    }

    public function getSession(): ?InMemoryDocumentSessionOperations
    {
        return $this->session;
    }
}

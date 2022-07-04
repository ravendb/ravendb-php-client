<?php

namespace RavenDB\Documents\Operations;

class PatchOperationResult
{
    private ?PatchStatus $status = null;
    private ?object $document = null;

    public function getStatus(): ?PatchStatus
    {
        return $this->status;
    }

    public function setStatus(?PatchStatus $status): void
    {
        $this->status = $status;
    }

    public function getDocument(): ?object
    {
        return $this->document;
    }

    public function setDocument(?object $document): void
    {
        $this->document = $document;
    }
}

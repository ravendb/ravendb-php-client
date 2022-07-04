<?php

namespace RavenDB\Documents\Operations;

class OperationIdResult
{
    private ?int $operationId = null;
    private ?string $operationNodeTag = null;

    public function getOperationId(): ?int
    {
        return $this->operationId;
    }

    public function setOperationId(?int $operationId): void
    {
        $this->operationId = $operationId;
    }

    public function getOperationNodeTag(): ?string
    {
        return $this->operationNodeTag;
    }

    public function setOperationNodeTag(?string $operationNodeTag): void
    {
        $this->operationNodeTag = $operationNodeTag;
    }
}

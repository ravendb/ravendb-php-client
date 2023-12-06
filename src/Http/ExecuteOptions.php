<?php

namespace RavenDB\Http;

class ExecuteOptions
{
    private ?ServerNode $chosenNode;
    private ?int $nodeIndex = null;
    private bool $shouldRetry = true;

    public function __construct()
    {
    }

    public function getChosenNode(): ?ServerNode
    {
        return $this->chosenNode;
    }

    public function setChosenNode(?ServerNode $chosenNode): void
    {
        $this->chosenNode = $chosenNode;
    }

    public function getNodeIndex(): ?int
    {
        return $this->nodeIndex;
    }

    public function setNodeIndex(?int $nodeIndex): void
    {
        $this->nodeIndex = $nodeIndex;
    }

    public function isShouldRetry(): bool
    {
        return $this->shouldRetry;
    }

    public function setShouldRetry(bool $shouldRetry): void
    {
        $this->shouldRetry = $shouldRetry;
    }
}

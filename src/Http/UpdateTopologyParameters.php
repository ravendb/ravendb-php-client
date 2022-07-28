<?php

namespace RavenDB\Http;

use RavenDB\Exceptions\IllegalArgumentException;

class UpdateTopologyParameters
{
    private ?ServerNode $node = null;
    private int $timeoutInMs = 15_000;
    private bool $forceUpdate = false;
    private ?string $debugTag = null;
    private ?string $applicationIdentifier = null;

    public function __construct(?ServerNode $node)
    {
        if ($node == null) {
            throw new IllegalArgumentException("node cannot be null");
        }
        $this->node = $node;
    }

    public function getNode(): ?ServerNode
    {
        return $this->node;
    }

    public function getTimeoutInMs(): int
    {
        return $this->timeoutInMs;
    }

    public function setTimeoutInMs(int $timeoutInMs): void
    {
        $this->timeoutInMs = $timeoutInMs;
    }

    public function isForceUpdate(): bool
    {
        return $this->forceUpdate;
    }

    public function setForceUpdate(bool $forceUpdate): void
    {
        $this->forceUpdate = $forceUpdate;
    }

    public function getDebugTag(): ?string
    {
        return $this->debugTag;
    }

    public function setDebugTag(?string $debugTag): void
    {
        $this->debugTag = $debugTag;
    }

    public function getApplicationIdentifier(): ?string
    {
        return $this->applicationIdentifier;
    }

    public function setApplicationIdentifier(?string $applicationIdentifier): void
    {
        $this->applicationIdentifier = $applicationIdentifier;
    }
}

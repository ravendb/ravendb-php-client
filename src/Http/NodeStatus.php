<?php

namespace RavenDB\Http;

use DateTime;

class NodeStatus
{
    private bool $connected = false;
    private ?string $errorDetails = null;
    private ?DateTime $lastSend = null;
    private ?DateTime $lastReply = null;
    private ?string $lastSentMessage = null;
    private int $lastMatchingIndex = 0;

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function setConnected(bool $connected): void
    {
        $this->connected = $connected;
    }

    public function getErrorDetails(): ?string
    {
        return $this->errorDetails;
    }

    public function setErrorDetails(?string $errorDetails): void
    {
        $this->errorDetails = $errorDetails;
    }

    public function getLastSend(): ?DateTime
    {
        return $this->lastSend;
    }

    public function setLastSend(?DateTime $lastSend): void
    {
        $this->lastSend = $lastSend;
    }

    public function getLastReply(): ?DateTime
    {
        return $this->lastReply;
    }

    public function setLastReply(?DateTime $lastReply): void
    {
        $this->lastReply = $lastReply;
    }

    public function getLastSentMessage(): ?string
    {
        return $this->lastSentMessage;
    }

    public function setLastSentMessage(?string $lastSentMessage): void
    {
        $this->lastSentMessage = $lastSentMessage;
    }

    public function getLastMatchingIndex(): int
    {
        return $this->lastMatchingIndex;
    }

    public function setLastMatchingIndex(int $lastMatchingIndex): void
    {
        $this->lastMatchingIndex = $lastMatchingIndex;
    }
}

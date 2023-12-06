<?php

namespace RavenDB\Documents\Identity;

use DateTimeInterface;
use RavenDB\Http\ResultInterface;

/**
 * The result of a NextHiLo operation
 */
class HiLoResult implements ResultInterface
{
    private ?string $prefix = null;
    private ?int $low = null;
    private ?int $high = null;
    private int $lastSize = 0;
    private ?string $serverTag = null;
    private ?DateTimeInterface $lastRangeAt = null;

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    public function setPrefix(?string $prefix): void
    {
        $this->prefix = $prefix;
    }

    public function getLow(): ?int
    {
        return $this->low;
    }

    public function setLow(?int $low): void
    {
        $this->low = $low;
    }

    public function getHigh(): ?int
    {
        return $this->high;
    }

    public function setHigh(?int $high): void
    {
        $this->high = $high;
    }

    public function getLastSize(): int
    {
        return $this->lastSize;
    }

    public function setLastSize(int $lastSize): void
    {
        $this->lastSize = $lastSize;
    }

    public function getServerTag(): ?string
    {
        return $this->serverTag;
    }

    public function setServerTag(?string $serverTag): void
    {
        $this->serverTag = $serverTag;
    }

    public function getLastRangeAt(): ?DateTimeInterface
    {
        return $this->lastRangeAt;
    }

    public function setLastRangeAt(?DateTimeInterface $lastRangeAt): void
    {
        $this->lastRangeAt = $lastRangeAt;
    }
}

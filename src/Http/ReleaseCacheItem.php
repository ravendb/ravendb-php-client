<?php

namespace RavenDB\Http;

use RavenDB\Primitives\CleanCloseable;
use RavenDB\Type\Duration;
use RavenDB\Utils\DateUtils;

class ReleaseCacheItem implements CleanCloseable
{
    private ?HttpCacheItem $item = null;
    private int $cacheGeneration;

    public function __construct(?HttpCacheItem $item = null)
    {
        $this->item = $item;
        $this->cacheGeneration = $item != null ? $item->generation : 0;
    }

    public function getItem(): ?HttpCacheItem
    {
        return $this->item;
    }

    public function notModified(): void
    {
        if ($this->item != null) {
            $this->item->lastServerUpdate = DateUtils::now();
            $this->item->generation = $this->cacheGeneration;
        }
    }

    public function getAge(): Duration
    {
        if ($this->item == null) {
            return Duration::ofMillis(PHP_INT_MAX);
        }
        return Duration::between($this->item->lastServerUpdate, DateUtils::now());
    }

    public function getMightHaveBeenModified(): bool
    {
        return false; // TBD
    }

    public function close(): void
    {
        // not implemented
    }
}

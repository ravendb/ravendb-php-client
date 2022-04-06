<?php

namespace RavenDB\Http;

use DateTime;
use RavenDB\Primitives\CleanCloseable;
use RavenDB\Type\Duration;

// !status: DONE
class ReleaseCacheItem implements CleanCloseable
{
    private ?HttpCacheItem $item = null;
    private int $cacheGeneration;

    public function __construct(?HttpCacheItem $item = null)
    {
        $this->item = $item;
        $this->cacheGeneration = $item != null ? $item->generation : 0;
    }

    public function notModified(): void
    {
        if ($this->item != null) {
            $this->item->lastServerUpdate = new DateTime();
            $this->item->generation = $this->cacheGeneration;
        }
    }

    public function getAge(): Duration
    {
        if ($this->item == null) {
            return Duration::ofMillis(PHP_INT_MAX);
        }
        return Duration::between($this->item->lastServerUpdate, new DateTime());
    }

    public function close(): void
    {
        // not implemented
    }
}

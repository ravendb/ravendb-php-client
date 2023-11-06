<?php

namespace RavenDB\Http;

use RavenDB\Primitives\CleanCloseable;
use RavenDB\Utils\AtomicInteger;

class HttpCache implements CleanCloseable
{
    public const NOT_FOUND_RESPONSE = "404 Response";

    private HttpCacheItemArray $items;

    public AtomicInteger $generation;

    // @todo: decide what to do with this property
    // unused at the moment. added just to store value that is passed in constructor.
    // maybe later we will require to decide how big cache can be
    private int $maxCacheSize;

    public function __construct(int $maxCacheSize)
    {
        $this->maxCacheSize = $maxCacheSize;

        $this->items = new HttpCacheItemArray();
        $this->generation = new AtomicInteger();
    }

    public function close(): void
    {
        $this->clear();
    }

    public function clear(): void
    {
        foreach ($this->items as $key => $item) {
            $this->items->offsetUnset($key);
        }
    }

    public function getNumberOfItems(): int
    {
        return $this->items->count();
    }

    public function set(string $url, string $changeVector, string $result): void
    {
        $httpCacheItem = new HttpCacheItem();

        $httpCacheItem->changeVector = $changeVector;
        $httpCacheItem->payload = $result;
        $httpCacheItem->cache = $this;
        $httpCacheItem->generation = $this->generation->get();

        $this->items->offsetSet($url, $httpCacheItem);
    }

    public function get(string $url, &$changeVectorRef, &$responseRef): ReleaseCacheItem
    {
        $item = $this->items->getIfPresent($url);

        if ($item != null) {
            $changeVectorRef = $item->changeVector;
            $responseRef = $item->payload;

            return new ReleaseCacheItem($item);
        }

        $changeVectorRef = null;
        $responseRef = null;
        return new ReleaseCacheItem();
    }

    public function setNotFound(string $url, bool $aggressivelyCached): void
    {
        $httpCacheItem = new HttpCacheItem();
        $httpCacheItem->changeVector = self::NOT_FOUND_RESPONSE;
        $httpCacheItem->cache = $this;
        $httpCacheItem->generation = $this->generation->get();

        $flags = new ItemFlagsSet();
        if ($aggressivelyCached) {
            $flags->append(ItemFlags::aggressivelyCached());
        }
        $flags->append(ItemFlags::notFound());

        $httpCacheItem->flags = $flags;

        $this->items->offsetSet($url, $httpCacheItem);
    }
}

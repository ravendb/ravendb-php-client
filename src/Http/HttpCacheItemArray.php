<?php

namespace RavenDB\Http;

use RavenDB\Type\TypedArray;

class HttpCacheItemArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(HttpCacheItem::class);
    }

    public function getIfPresent(string $url): ?HttpCacheItem
    {
        if (!$this->offsetExists($url)) {
            return null;
        }

        return $this->offsetGet($url);
    }
}

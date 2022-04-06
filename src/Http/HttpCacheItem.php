<?php

namespace RavenDB\Http;

use DateTime;
use DateTimeInterface;

class HttpCacheItem
{
    public string $changeVector;
    public string $payload;
    public DateTimeInterface $lastServerUpdate;
    public int $generation;
    public ItemFlagsSet $flags;

    public HttpCache $cache;

    public function __construct() {
        $this->lastServerUpdate = new DateTime();

        $this->flags = new ItemFlagsSet();
        $this->flags[] = ItemFlags::none();
    }
}

<?php

namespace RavenDB\Http;

use DateTime;
use DateTimeInterface;

class HttpCacheItem
{
    public ?string $changeVector = null;
    public ?string $payload = null;
    public ?DateTimeInterface $lastServerUpdate = null;
    public int $generation;
    public ItemFlagsSet $flags;

    public ?HttpCache $cache = null;

    public function __construct() {
        $this->flags = new ItemFlagsSet();
        $this->flags[] = ItemFlags::none();
    }
}

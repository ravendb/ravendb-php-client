<?php

namespace RavenDB\Documents\Session;

use DateTime;
use Ds\Map as DSMap;

class DocumentInfoDates
{
    private ?DSMap $map = null;

    public function __construct()
    {
        $this->map = new DSMap();
    }

    public function clear(): void
    {
        $this->map->clear();
    }

    public function put(DateTime $key, DocumentInfo $value): void
    {
        $this->map->put($key, $value);
    }

    public function get(DateTime $key): ?DocumentInfo
    {
        return $this->map->get($key);
    }
}

<?php

namespace RavenDB\Documents\Session;

use Iterator;

class DocumentsById implements Iterator
{
    private int $position = 0;
    private array $inner = [];

    public function getValue(string $id): DocumentInfo
    {
        return $this->inner[$id];
    }

    public function add(DocumentInfo $info): void
    {
        if (array_key_exists($info->getId(), $this->inner)) {
            return;
        }

        $this->inner[$info->getId()] = $info;
    }

    public function remove(string $id): void
    {
        unset($this->inner[$id]);
    }

    public function clear(): void
    {
        $this->inner = [];
    }

    public function getCount(): int
    {
        return count($this->inner);
    }

    public function current()
    {
        return $this->inner[$this->position];
    }

    public function next()
    {
        ++$this->position;
    }

    public function key()
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return isset($this->inner[$this->position]);
    }

    public function rewind()
    {
        $this->position = 0;
    }
}

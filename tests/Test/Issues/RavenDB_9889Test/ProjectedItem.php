<?php

namespace tests\RavenDB\Test\Issues\RavenDB_9889Test;

class ProjectedItem
{
    private bool $before = false;
    private bool $after = false;

    public function isBefore(): bool
    {
        return $this->before;
    }

    public function setBefore(bool $before): void
    {
        $this->before = $before;
    }

    public function isAfter(): bool
    {
        return $this->after;
    }

    public function setAfter(bool $after): void
    {
        $this->after = $after;
    }
}

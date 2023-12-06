<?php

namespace RavenDB\Http;

use RavenDB\Type\Duration;

class AggressiveCacheOptions
{
    private Duration $duration;
    private AggressiveCacheMode $mode;

    public function __construct(Duration $duration, AggressiveCacheMode $mode)
    {
        $this->duration = $duration;
        $this->mode = $mode;
    }

    public function getMode(): AggressiveCacheMode
    {
        return $this->mode;
    }

    public function setMode(AggressiveCacheMode $mode): void
    {
        $this->mode = $mode;
    }

    public function getDuration(): Duration
    {
        return $this->duration;
    }

    public function setDuration(Duration $duration): void
    {
        $this->duration = $duration;
    }
}

<?php

namespace RavenDB\Documents\Session;

use RavenDB\Type\Duration;

class ResponseTimeItem
{
    private ?string $url = null;
    private ?Duration $duration = null;

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function getDuration(): ?Duration
    {
        return $this->duration;
    }

    public function setDuration(?Duration $duration): void
    {
        $this->duration = $duration;
    }
}

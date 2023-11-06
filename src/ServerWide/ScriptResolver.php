<?php

namespace RavenDB\ServerWide;

use DateTimeInterface;

class ScriptResolver
{
    private string $script;
    private DateTimeInterface $lastModifiedTime;

    public function getScript(): string
    {
        return $this->script;
    }

    public function setScript(string $script): void
    {
        $this->script = $script;
    }

    public function getLastModifiedTime(): DateTimeInterface
    {
        return $this->lastModifiedTime;
    }

    public function setLastModifiedTime(DateTimeInterface $lastModifiedTime): void
    {
        $this->lastModifiedTime = $lastModifiedTime;
    }
}

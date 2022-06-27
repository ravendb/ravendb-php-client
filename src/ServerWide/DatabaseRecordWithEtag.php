<?php

namespace RavenDB\ServerWide;

class DatabaseRecordWithEtag extends DatabaseRecord
{
    private ?int $etag = null;

    public function getEtag(): ?int
    {
        return $this->etag;
    }

    public function setEtag(?int $etag): void
    {
        $this->etag = $etag;
    }
}

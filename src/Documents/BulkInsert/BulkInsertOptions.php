<?php

namespace RavenDB\Documents\BulkInsert;

class BulkInsertOptions
{
    private bool $useCompression = false;
    private bool $skipOverwriteIfUnchanged = false;

    public function isUseCompression(): bool
    {
        return $this->useCompression;
    }

    public function setUseCompression(bool $useCompression): void
    {
        $this->useCompression = $useCompression;
    }

    /**
     * Determines whether we should skip overwriting a document when it is updated by exactly the same document (by comparing the content and the metadata)
     *
     * @return bool
     */
    public function isSkipOverwriteIfUnchanged(): bool
    {
        return $this->skipOverwriteIfUnchanged;
    }

    /**
     * Determines whether we should skip overwriting a document when it is updated by exactly the same document (by comparing the content and the metadata)
     *
     * @param bool $skipOverwriteIfUnchanged
     */
    public function setSkipOverwriteIfUnchanged(bool $skipOverwriteIfUnchanged): void
    {
        $this->skipOverwriteIfUnchanged = $skipOverwriteIfUnchanged;
    }
}

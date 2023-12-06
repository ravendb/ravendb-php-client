<?php

namespace RavenDB\Documents\Indexes;

class CollectionStats
{
    private ?int $lastProcessedDocumentEtag = null;
    private ?int $lastProcessedTombstoneEtag = null;
    private int $documentLag = -1;
    private int $tombstoneLag = -1;

    public function getLastProcessedDocumentEtag(): ?int
    {
        return $this->lastProcessedDocumentEtag;
    }

    public function setLastProcessedDocumentEtag(?int $lastProcessedDocumentEtag): void
    {
        $this->lastProcessedDocumentEtag = $lastProcessedDocumentEtag;
    }

    public function getLastProcessedTombstoneEtag(): ?int
    {
        return $this->lastProcessedTombstoneEtag;
    }

    public function setLastProcessedTombstoneEtag(?int $lastProcessedTombstoneEtag): void
    {
        $this->lastProcessedTombstoneEtag = $lastProcessedTombstoneEtag;
    }

    public function getDocumentLag(): ?int
    {
        return $this->documentLag;
    }

    public function setDocumentLag(?int $documentLag): void
    {
        $this->documentLag = $documentLag;
    }

    public function getTombstoneLag(): ?int
    {
        return $this->tombstoneLag;
    }

    public function setTombstoneLag(?int $tombstoneLag): void
    {
        $this->tombstoneLag = $tombstoneLag;
    }
}

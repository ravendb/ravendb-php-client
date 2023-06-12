<?php

namespace RavenDB\ServerWide\Operations\DocumentsCompression;

class DocumentCompressionConfigurationResult
{
    private ?int $raftCommandIndex = null;

    public function getRaftCommandIndex(): ?int
    {
        return $this->raftCommandIndex;
    }

    public function setRaftCommandIndex(?int $raftCommandIndex): void
    {
        $this->raftCommandIndex = $raftCommandIndex;
    }
}

<?php

namespace RavenDB\Documents\Commands\Batches;

class BatchOptions
{
    private ?ReplicationBatchOptions $replicationOptions = null;
    private ?IndexBatchOptions $indexOptions = null;

    public function getReplicationOptions(): ?ReplicationBatchOptions
    {
        return $this->replicationOptions;
    }

    public function setReplicationOptions(ReplicationBatchOptions $replicationOptions): void
    {
        $this->replicationOptions = $replicationOptions;
    }

    public function getIndexOptions(): ?IndexBatchOptions
    {
        return $this->indexOptions;
    }

    public function setIndexOptions(IndexBatchOptions $indexOptions): void
    {
        $this->indexOptions = $indexOptions;
    }
}

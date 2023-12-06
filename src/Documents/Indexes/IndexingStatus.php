<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Http\ResultInterface;

class IndexingStatus implements ResultInterface
{
    private ?IndexRunningStatus $status = null;

    private ?IndexStatusArray $indexes = null;

    public function getStatus(): ?IndexRunningStatus
    {
        return $this->status;
    }

    public function setStatus(?IndexRunningStatus $status): void
    {
        $this->status = $status;
    }

    public function getIndexes(): ?IndexStatusArray
    {
        return $this->indexes;
    }

    public function setIndexes(?IndexStatusArray $indexes): void
    {
        $this->indexes = $indexes;
    }
}

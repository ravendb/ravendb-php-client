<?php

namespace RavenDB\ServerWide\Operations;

use RavenDB\Http\ResultInterface;
use RavenDB\Type\StringArray;

class DeleteDatabaseResult implements ResultInterface
{
    private int $raftCommandIndex;
    private StringArray $pendingDeletes;

    public function getRaftCommandIndex(): int
    {
        return $this->raftCommandIndex;
    }

    public function setRaftCommandIndex(int $raftCommandIndex): void
    {
        $this->raftCommandIndex = $raftCommandIndex;
    }

    public function getPendingDeletes(): StringArray
    {
        return $this->pendingDeletes;
    }

    public function setPendingDeletes(StringArray $pendingDeletes): void
    {
        $this->pendingDeletes = $pendingDeletes;
    }
}

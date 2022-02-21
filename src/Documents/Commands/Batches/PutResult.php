<?php

namespace RavenDB\Documents\Commands\Batches;

use RavenDB\Http\ResultInterface;

class PutResult implements ResultInterface
{
    private string $id;

    private string $changeVector;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getChangeVector(): string
    {
        return $this->changeVector;
    }

    public function setChangeVector(string $changeVector): void
    {
        $this->changeVector = $changeVector;
    }
}

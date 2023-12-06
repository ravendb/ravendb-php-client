<?php

namespace RavenDB\Documents\Commands\Batches;

class IdAndChangeVector
{
    private ?string $id = null;
    private ?string $changeVector = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getChangeVector(): ?string
    {
        return $this->changeVector;
    }

    public function setChangeVector(?string $changeVector): void
    {
        $this->changeVector = $changeVector;
    }

    public static function create(?string $id, ?string $changeVector): IdAndChangeVector
    {
        $idAndChangeVector = new IdAndChangeVector();
        $idAndChangeVector->setId($id);
        $idAndChangeVector->setChangeVector($changeVector);
        return $idAndChangeVector;
    }
}

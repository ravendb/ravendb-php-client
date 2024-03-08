<?php

namespace RavenDB\Documents\Session;

class EntityInfo
{
    private ?string $id = null;
    private ?array $entity = null;
    private bool $isDeleted = false;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getEntity(): ?array
    {
        return $this->entity;
    }

    public function setEntity(?array $entity): void
    {
        $this->entity = $entity;
    }

    public function isDeleted(): ?bool
    {
        return $this->isDeleted;
    }

    public function setDeleted(?bool $isDeleted): void
    {
        $this->isDeleted = $isDeleted;
    }
}

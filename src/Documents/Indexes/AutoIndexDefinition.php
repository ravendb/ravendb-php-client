<?php

namespace RavenDB\Documents\Indexes;

class AutoIndexDefinition
{
    private ?IndexType $type = null;
    private ?string $name = null;
    private ?IndexPriority $priority = null;
    private ?IndexState $state = null;
    private ?string $collection = null;
    private ?AutoIndexFieldOptionsMap $mapFields = null;
    private ?AutoIndexFieldOptionsMap $groupByFields = null;

    public function getType(): ?IndexType
    {
        return $this->type;
    }

    public function setType(?IndexType $type): void
    {
        $this->type = $type;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getPriority(): ?IndexPriority
    {
        return $this->priority;
    }

    public function setPriority(?IndexPriority $priority): void
    {
        $this->priority = $priority;
    }

    public function getState(): ?IndexState
    {
        return $this->state;
    }

    public function setState(?IndexState $state): void
    {
        $this->state = $state;
    }

    public function getCollection(): ?string
    {
        return $this->collection;
    }

    public function setCollection(?string $collection): void
    {
        $this->collection = $collection;
    }

    public function getMapFields(): ?AutoIndexFieldOptionsMap
    {
        return $this->mapFields;
    }

    public function setMapFields(?AutoIndexFieldOptionsMap $mapFields): void
    {
        $this->mapFields = $mapFields;
    }

    public function getGroupByFields(): ?AutoIndexFieldOptionsMap
    {
        return $this->groupByFields;
    }

    public function setGroupByFields(?AutoIndexFieldOptionsMap $groupByFields): void
    {
        $this->groupByFields = $groupByFields;
    }
}

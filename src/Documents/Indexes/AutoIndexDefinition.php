<?php

namespace RavenDB\Documents\Indexes;

class AutoIndexDefinition extends IndexDefinitionBase
{
    private ?IndexType $type = null;

    private ?string $collection = null;
    private ?AutoIndexFieldOptionsMap $mapFields = null;
    private ?AutoIndexFieldOptionsMap $groupByFields = null;

    public function __construct()
    {
        parent::__construct();
    }

    public function getType(): ?IndexType
    {
        return $this->type;
    }

    public function setType(?IndexType $type): void
    {
        $this->type = $type;
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

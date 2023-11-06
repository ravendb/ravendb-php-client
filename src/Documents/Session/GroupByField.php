<?php

namespace RavenDB\Documents\Session;

class GroupByField
{
    private ?string $fieldName = null;
    private ?string $projectedName = null;

    public function __construct(?string $fieldName = null, ?string $projectedName = null)
    {
        $this->fieldName = $fieldName;
        $this->projectedName = $projectedName;
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function setFieldName(?string $fieldName): void
    {
        $this->fieldName = $fieldName;
    }

    public function getProjectedName(): ?string
    {
        return $this->projectedName;
    }

    public function setProjectedName(?string $projectedName): void
    {
        $this->projectedName = $projectedName;
    }
}

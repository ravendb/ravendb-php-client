<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Documents\Indexes\IndexPriority;
use RavenDB\Type\StringArray;
use Symfony\Component\Serializer\Annotation\SerializedName;

class IndexPriorityParameters
{
    /** @SerializedName ("IndexNames") */
    private ?StringArray $indexNames = null;

    /** @SerializedName ("Priority") */
    private ?IndexPriority $priority = null;

    public function getIndexNames(): ?StringArray
    {
        return $this->indexNames;
    }

    public function setIndexNames(?StringArray $indexNames): void
    {
        $this->indexNames = $indexNames;
    }

    public function getPriority(): ?IndexPriority
    {
        return $this->priority;
    }

    public function setPriority(?IndexPriority $priority): void
    {
        $this->priority = $priority;
    }
}

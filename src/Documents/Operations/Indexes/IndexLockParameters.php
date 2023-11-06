<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Documents\Indexes\IndexLockMode;
use RavenDB\Type\StringArray;
use Symfony\Component\Serializer\Annotation\SerializedName;

class IndexLockParameters
{
    /** @SerializedName ("IndexNames") */
    private ?StringArray $indexNames = null;

    /** @SerializedName ("Mode") */
    private ?IndexLockMode $mode = null;

    public function getIndexNames(): ?StringArray
    {
        return $this->indexNames;
    }

    public function setIndexNames(?StringArray $indexNames): void
    {
        $this->indexNames = $indexNames;
    }

    public function getMode(): ?IndexLockMode
    {
        return $this->mode;
    }

    public function setMode(?IndexLockMode $mode): void
    {
        $this->mode = $mode;
    }
}

<?php

namespace RavenDB\ServerWide\Operations\OngoingTasks;

use RavenDB\Type\StringArray;
use RavenDB\ServerWide\DatabaseLockMode;

use Symfony\Component\Serializer\Annotation\SerializedName;

class SetDatabasesLockParameters
{
    /** @SerializedName ("DatabaseNames") */
    private ?StringArray $databaseNames = null;

    /** @SerializedName ("Mode") */
    private ?DatabaseLockMode $mode = null;

    public function getDatabaseNames(): ?StringArray
    {
        return $this->databaseNames;
    }

    /**
     * @param StringArray|array|null $databaseNames
     */
    public function setDatabaseNames($databaseNames): void
    {
        if (is_array($databaseNames)) {
            $databaseNames = StringArray::fromArray($databaseNames);
        }
        $this->databaseNames = $databaseNames;
    }

    public function getMode(): ?DatabaseLockMode
    {
        return $this->mode;
    }

    public function setMode(?DatabaseLockMode $mode): void
    {
        $this->mode = $mode;
    }
}

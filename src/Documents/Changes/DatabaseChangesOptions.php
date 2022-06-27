<?php

namespace RavenDB\Documents\Changes;

// !status: DONE
class DatabaseChangesOptions
{
    private ?string $databaseName = null;
    private ?string $nodeTag = null;

    public function __construct(?string $databaseName = null, ?string $nodeTag = null)
    {
        $this->databaseName = $databaseName;
        $this->nodeTag = $nodeTag;
    }

    public function getDatabaseName(): ?string
    {
        return $this->databaseName;
    }

    public function setDatabaseName(?string $databaseName): void
    {
        $this->databaseName = $databaseName;
    }

    public function getNodeTag(): ?string
    {
        return $this->nodeTag;
    }

    public function setNodeTag(?string $nodeTag): void
    {
        $this->nodeTag = $nodeTag;
    }

    public function equals(?object $o): bool
    {
        if ($this == $o) return true;
        if ($o == null || get_class($this) != get_class($o)) return false;
        /** @var DatabaseChangesOptions $that */
        $that = $o;
        return strtolower($this->databaseName) == strtolower($that->getDatabaseName()) &&
            ($this->nodeTag == $that->getNodeTag());
    }
}

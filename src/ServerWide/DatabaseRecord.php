<?php

namespace RavenDB\ServerWide;

class DatabaseRecord
{
    private string $databaseName;
    private ?DatabaseTopology $topology = null;

    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    public function setDatabaseName(string $databaseName): void
    {
        $this->databaseName = $databaseName;
    }

    public function getTopology(): ?DatabaseTopology
    {
        return $this->topology;
    }

    public function setTopology(?DatabaseTopology $topology): void
    {
        $this->topology = $topology;
    }
}

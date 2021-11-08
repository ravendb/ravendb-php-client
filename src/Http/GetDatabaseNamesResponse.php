<?php

namespace RavenDB\Http;

use RavenDB\Type\StringArray;

class GetDatabaseNamesResponse implements ResultInterface
{
    private array $databases;

    public function toArray(): array
    {
        return $this->databases;//->getArrayCopy();
    }

    public function getDatabases(): array
    {
        return $this->databases;
    }

    public function setDatabases(array $databases): void
    {
        $this->databases = $databases;
    }
}

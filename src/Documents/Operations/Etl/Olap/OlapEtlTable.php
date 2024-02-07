<?php

namespace RavenDB\Documents\Operations\Etl\Olap;

class OlapEtlTable
{
    private ?string $tableName = null;
    private ?string $documentIdColumn = null;

    public function getTableName(): ?string
    {
        return $this->tableName;
    }

    public function setTableName(?string $tableName): void
    {
        $this->tableName = $tableName;
    }

    public function getDocumentIdColumn(): ?string
    {
        return $this->documentIdColumn;
    }

    public function setDocumentIdColumn(?string $documentIdColumn): void
    {
        $this->documentIdColumn = $documentIdColumn;
    }
}

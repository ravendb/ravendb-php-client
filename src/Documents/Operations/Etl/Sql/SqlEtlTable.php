<?php

namespace RavenDB\Documents\Operations\Etl\Sql;

use Symfony\Component\Serializer\Annotation\SerializedName;

class SqlEtlTable
{
    #[SerializedName("TableName")]
    private ?string $tableName = null;

    #[SerializedName("DocumentIdColumn")]
    private ?string $documentIdColumn = null;

    #[SerializedName("InsertOnlyMode")]
    private bool $insertOnlyMode = false;

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

    public function isInsertOnlyMode(): bool
    {
        return $this->insertOnlyMode;
    }

    public function setInsertOnlyMode(bool $insertOnlyMode): void
    {
        $this->insertOnlyMode = $insertOnlyMode;
    }

}

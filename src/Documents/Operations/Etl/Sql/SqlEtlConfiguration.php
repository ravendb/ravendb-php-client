<?php

namespace RavenDB\Documents\Operations\Etl\Sql;

use RavenDB\Documents\Operations\Etl\EtlConfiguration;
use RavenDB\Documents\Operations\Etl\EtlType;
use Symfony\Component\Serializer\Annotation\SerializedName;

class SqlEtlConfiguration extends EtlConfiguration
{
    #[SerializedName("ParameterizeDeletes")]
    private bool $parameterizeDeletes = false;

    #[SerializedName("ForceQueryRecompile")]
    private bool $forceQueryRecompile = false;

    #[SerializedName("QuoteTables")]
    private bool $quoteTables = false;

    #[SerializedName("CommandTimeout")]
    private ?int $commandTimeout = null;

    #[SerializedName("SqlTables")]
    private ?SqlEtlTableList $sqlTables = null;

    #[SerializedName("EtlType")]
    private ?EtlType $etlType = null;

    public function __construct()
    {
        parent::__construct();

        $this->etlType = EtlType::sql();
    }

    public function getEtlType(): ?EtlType
    {
        return $this->etlType;
    }

    public function isParameterizeDeletes(): bool
    {
        return $this->parameterizeDeletes;
    }

    public function setParameterizeDeletes(bool $parameterizeDeletes): void
    {
        $this->parameterizeDeletes = $parameterizeDeletes;
    }

    public function isForceQueryRecompile(): bool
    {
        return $this->forceQueryRecompile;
    }

    public function setForceQueryRecompile(bool $forceQueryRecompile): void
    {
        $this->forceQueryRecompile = $forceQueryRecompile;
    }

    public function isQuoteTables(): bool
    {
        return $this->quoteTables;
    }

    public function setQuoteTables(bool $quoteTables): void
    {
        $this->quoteTables = $quoteTables;
    }

    public function getCommandTimeout(): ?int
    {
        return $this->commandTimeout;
    }

    public function setCommandTimeout(?int $commandTimeout): void
    {
        $this->commandTimeout = $commandTimeout;
    }

    public function getSqlTables(): ?SqlEtlTableList
    {
        return $this->sqlTables;
    }

    public function setSqlTables(null|SqlEtlTableList|array $sqlTables): void
    {
        $this->sqlTables = is_array($sqlTables) ? SqlEtlTableList::fromArray($sqlTables) : $sqlTables;
    }

}

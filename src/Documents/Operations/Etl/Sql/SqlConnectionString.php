<?php

namespace RavenDB\Documents\Operations\Etl\Sql;

use RavenDB\Documents\Operations\ConnectionStrings\ConnectionString;
use RavenDB\ServerWide\ConnectionStringType;
use Symfony\Component\Serializer\Annotation\SerializedName;

class SqlConnectionString extends ConnectionString
{
    #[SerializedName("ConnectionString")]
    private ?string $connectionString = null;

    #[SerializedName("FactoryName")]
    private ?string $factoryName = null;

    #[SerializedName("Type")]
    private ?ConnectionStringType $type = null;

    public function getType(): ConnectionStringType
    {
        return $this->type;
    }

    public function __construct()
    {
        $this->type = ConnectionStringType::sql();
    }

    public function getConnectionString(): ?string
    {
        return $this->connectionString;
    }

    public function setConnectionString(?string $connectionString): void
    {
        $this->connectionString = $connectionString;
    }

    public function getFactoryName(): ?string
    {
        return $this->factoryName;
    }

    public function setFactoryName(?string $factoryName): void
    {
        $this->factoryName = $factoryName;
    }
}

<?php

namespace RavenDB\Documents\Operations\ConnectionStrings;

use RavenDB\ServerWide\ConnectionStringType;
use Symfony\Component\Serializer\Annotation\SerializedName;

abstract class ConnectionString
{
    #[SerializedName("Name")]
    private ?string $name = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    abstract public function getType(): ConnectionStringType;
}

<?php

namespace RavenDB\Documents\Operations\Etl\Queue;

class RabbitMqConnectionSettings
{
    private ?string $connectionString = null;

    public function getConnectionString(): ?string
    {
        return $this->connectionString;
    }

    public function setConnectionString(?string $connectionString): void
    {
        $this->connectionString = $connectionString;
    }
}

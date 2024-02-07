<?php

namespace RavenDB\Documents\Operations\Etl\Queue;

class KafkaConnectionSettings
{
    private ?string $bootstrapServers = null;

    private ?array $connectionOptions = null;

    private bool $useRavenCertificate = false;

    public function getBootstrapServers(): ?string
    {
        return $this->bootstrapServers;
    }

    public function setBootstrapServers(?string $bootstrapServers): void
    {
        $this->bootstrapServers = $bootstrapServers;
    }

    public function getConnectionOptions(): ?array
    {
        return $this->connectionOptions;
    }

    public function setConnectionOptions(?array $connectionOptions): void
    {
        $this->connectionOptions = $connectionOptions;
    }

    public function isUseRavenCertificate(): bool
    {
        return $this->useRavenCertificate;
    }

    public function setUseRavenCertificate(bool $useRavenCertificate): void
    {
        $this->useRavenCertificate = $useRavenCertificate;
    }
}

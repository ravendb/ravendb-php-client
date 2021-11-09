<?php

namespace RavenDB\ServerWide;

class DatabaseTopology
{
    private int $replicationFactor = 1;

    public function getReplicationFactor(): int
    {
        return $this->replicationFactor;
    }

    public function setReplicationFactor(int $replicationFactor): void
    {
        $this->replicationFactor = $replicationFactor;
    }
}

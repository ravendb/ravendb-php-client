<?php

namespace RavenDB\ServerWide\Operations;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\ServerWide\DatabaseRecord;

class CreateDatabaseOperation implements ServerOperationInterface
{
    private DatabaseRecord $databaseRecord;
    private int $replicationFactor;

    public function __construct(DatabaseRecord $databaseRecord, ?int $replicationFactor = null)
    {
        $this->databaseRecord = $databaseRecord;

        if (($replicationFactor != null) && ($replicationFactor > 0)) {
            $this->replicationFactor = $replicationFactor;
            return;
        }

        $topology = $databaseRecord->getTopology();
        if (($topology != null) && ($topology->getReplicationFactor() > 0)) {
            $this->replicationFactor = $topology->getReplicationFactor();
            return;
        }

        $this->replicationFactor = 1;
    }


    public function getCommand(DocumentConventions $conventions): CreateDatabaseCommand
    {
        return new CreateDatabaseCommand($conventions, $this->databaseRecord, $this->replicationFactor);
    }
}

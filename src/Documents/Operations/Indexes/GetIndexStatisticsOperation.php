<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\GetStatisticsCommand;
use RavenDB\Documents\Operations\MaintenanceOperationInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\RavenCommand;

class GetIndexStatisticsOperation implements MaintenanceOperationInterface
{
    private ?string $indexName = null;

    public function __construct($indexName)
    {
        if ($indexName == null) {
            throw new IllegalArgumentException("IndexName cannot be null");
        }

        $this->indexName = $indexName;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new GetIndexStatisticsCommand($this->indexName);
    }
}

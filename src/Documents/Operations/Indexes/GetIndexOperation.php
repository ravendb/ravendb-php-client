<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\MaintenanceOperationInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\RavenCommand;

class GetIndexOperation implements MaintenanceOperationInterface
{
    private ?string $indexName = null;

    public function __construct(?string $indexName)
    {
        if ($indexName == null) {
            throw new IllegalArgumentException("IndexName cannot be null");
        }

        $this->indexName = $indexName;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new GetIndexCommand($this->indexName);
    }
}

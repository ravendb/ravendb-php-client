<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\VoidMaintenanceOperationInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\VoidRavenCommand;

class StopIndexOperation implements VoidMaintenanceOperationInterface
{
    private ?string $indexName = null;

    public function __construct(?string $indexName)
    {
        if ($indexName == null) {
            throw new IllegalArgumentException("Index name cannot be null");
        }

        $this->indexName = $indexName;
    }

    function getCommand(DocumentConventions $conventions): VoidRavenCommand
    {
        return new StopIndexCommand($this->indexName);
    }
}

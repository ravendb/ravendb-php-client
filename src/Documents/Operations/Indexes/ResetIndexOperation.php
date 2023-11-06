<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\VoidMaintenanceOperationInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\VoidRavenCommand;

class ResetIndexOperation implements VoidMaintenanceOperationInterface
{
    private ?string $indexName = null;

    public function __construct(?string $indexName)
    {
        if ($indexName == null) {
            throw new IllegalArgumentException("Index name cannot be null");
        }

        $this->indexName = $indexName;
    }

    public function getCommand(DocumentConventions $conventions): VoidRavenCommand
    {
        return new ResetIndexCommand($this->indexName);
    }
}

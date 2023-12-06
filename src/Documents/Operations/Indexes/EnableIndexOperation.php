<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\VoidMaintenanceOperationInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\VoidRavenCommand;

class EnableIndexOperation implements VoidMaintenanceOperationInterface
{
    private ?string $indexName = null;
    private bool $clusterWide = false;

    public function __construct(?string $indexName, bool $clusterWide = false)
    {
        if ($indexName == null) {
            throw new IllegalArgumentException("IndexName cannot be null");
        }

        $this->indexName = $indexName;
        $this->clusterWide = $clusterWide;
    }

    function getCommand(DocumentConventions $conventions): VoidRavenCommand
    {
        return new EnableIndexCommand($this->indexName, $this->clusterWide);
    }
}

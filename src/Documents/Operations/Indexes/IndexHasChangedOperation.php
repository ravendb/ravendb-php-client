<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Indexes\IndexDefinition;
use RavenDB\Documents\Operations\MaintenanceOperationInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\RavenCommand;

class IndexHasChangedOperation implements MaintenanceOperationInterface
{
    private ?IndexDefinition $definition = null;

    public function __construct(?IndexDefinition $definition)
    {
        if ($definition == null) {
            throw new IllegalArgumentException("IndexDefinition cannot be null");
        }

        $this->definition = $definition;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new IndexHasChangedCommand($conventions, $this->definition);
    }
}

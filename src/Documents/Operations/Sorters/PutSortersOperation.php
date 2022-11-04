<?php

namespace RavenDB\Documents\Operations\Sorters;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\VoidMaintenanceOperationInterface;
use RavenDB\Documents\Queries\Sorting\SorterDefinition;
use RavenDB\Documents\Queries\Sorting\SorterDefinitionArray;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\VoidRavenCommand;

class PutSortersOperation implements VoidMaintenanceOperationInterface
{
    private SorterDefinitionArray $sortersToAdd;

    public function __construct(SorterDefinition ...$sortersToAdd) {
        if (empty($sortersToAdd)) {
            throw new IllegalArgumentException("SortersToAdd cannot be null or empty");
        }

        $this->sortersToAdd = SorterDefinitionArray::fromArray($sortersToAdd);
    }

    function getCommand(DocumentConventions $conventions): VoidRavenCommand
    {
        return new PutSortersCommand($conventions, $this->sortersToAdd);
    }
}

<?php

namespace RavenDB\ServerWide\Sorters;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Queries\Sorting\SorterDefinition;
use RavenDB\Documents\Queries\Sorting\SorterDefinitionArray;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\ServerWide\Operations\VoidServerOperationInterface;

class PutServerWideSortersOperation implements VoidServerOperationInterface
{
    private ?SorterDefinitionArray $sortersToAdd = null;

    public function __construct(?SorterDefinition ...$sortersToAdd)
    {
        if ($sortersToAdd == null || count($sortersToAdd) == 0) {
            throw new IllegalArgumentException("SortersToAdd cannot be null or empty");
        }

        $this->sortersToAdd = SorterDefinitionArray::fromArray($sortersToAdd);
    }

    public function getCommand(DocumentConventions $conventions): VoidRavenCommand
    {
        return new PutServerWideSortersCommand($conventions, $this->sortersToAdd);
    }
}

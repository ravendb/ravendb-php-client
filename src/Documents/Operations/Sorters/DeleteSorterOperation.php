<?php

namespace RavenDB\Documents\Operations\Sorters;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\VoidMaintenanceOperationInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\VoidRavenCommand;

class DeleteSorterOperation implements VoidMaintenanceOperationInterface
{
    private ?string $sorterName = null;

    public function __construct(?string $sorterName)
    {
        if (empty($sorterName)) {
            throw new IllegalArgumentException("SorterName cannot be null");
        }

        $this->sorterName = $sorterName;
    }

    public function getCommand(DocumentConventions $conventions): VoidRavenCommand
    {
        return new DeleteSorterCommand($this->sorterName);
    }
}

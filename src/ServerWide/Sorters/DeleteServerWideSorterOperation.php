<?php

namespace RavenDB\ServerWide\Sorters;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\ServerWide\Operations\VoidServerOperationInterface;

class DeleteServerWideSorterOperation implements VoidServerOperationInterface
{
    private ?string $sorterName = null;

    public function __construct(?string $sorterName)
    {
        if ($sorterName == null) {
            throw new IllegalArgumentException("SorterName cannot be null");
        }

        $this->sorterName = $sorterName;
    }

    public function getCommand(DocumentConventions $conventions): VoidRavenCommand
    {
        return new DeleteServerWideSorterCommand($this->sorterName);
    }

}

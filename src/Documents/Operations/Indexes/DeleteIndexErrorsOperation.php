<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\VoidMaintenanceOperationInterface;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\Type\StringArray;

// !status: DONE
class DeleteIndexErrorsOperation implements VoidMaintenanceOperationInterface
{
    private ?StringArray $indexNames = null;

    public function __construct(?StringArray $indexNames = null)
    {
        $this->indexNames = $indexNames;
    }

    function getCommand(DocumentConventions $conventions): VoidRavenCommand
    {
        return new DeleteIndexErrorsCommand($this->indexNames);
    }
}

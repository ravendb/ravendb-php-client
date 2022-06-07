<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\MaintenanceOperationInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Type\StringArray;

// !status: DONE
class GetIndexErrorsOperation implements MaintenanceOperationInterface
{
    private ?StringArray $indexNames = null;

    public function __construct(?StringArray $indexNames = null)
    {
        $this->indexNames = $indexNames;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new GetIndexErrorsCommand($this->indexNames);
    }
}

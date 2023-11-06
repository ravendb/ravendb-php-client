<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\VoidMaintenanceOperationInterface;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\Type\StringArray;

class DeleteIndexErrorsOperation implements VoidMaintenanceOperationInterface
{
    private ?StringArray $indexNames = null;

    /**
     * @param StringArray|array|null $indexNames
     */
    public function __construct($indexNames = null)
    {
        if (is_array($indexNames)) {
            $indexNames = StringArray::fromArray($indexNames);
        }
        $this->indexNames = $indexNames;
    }

    function getCommand(DocumentConventions $conventions): VoidRavenCommand
    {
        return new DeleteIndexErrorsCommand($this->indexNames);
    }
}

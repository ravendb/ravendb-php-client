<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\MaintenanceOperationInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Type\StringArray;

class GetIndexErrorsOperation implements MaintenanceOperationInterface
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

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new GetIndexErrorsCommand($this->indexNames);
    }
}

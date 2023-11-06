<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\MaintenanceOperationInterface;
use RavenDB\Http\RavenCommand;

class GetIndexesOperation implements MaintenanceOperationInterface
{
    private ?int $start = null;
    private ?int $pageSize = null;

    public function __construct(?int $start = null, ?int $pageSize = null) {
        $this->start = $start;
        $this->pageSize = $pageSize;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new GetIndexesCommand($this->start, $this->pageSize);
    }
}

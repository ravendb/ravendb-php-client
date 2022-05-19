<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\MaintenanceOperationInterface;
use RavenDB\Http\RavenCommand;

class GetIndexNamesOperation implements MaintenanceOperationInterface
{
    private ?int $start;
    private ?int $pageSize;

    public function __construct(?int $start, ?int $pageSize)
    {
        $this->start = $start;
        $this->pageSize = $pageSize;
    }

    public function getCommand(?DocumentConventions $conventions): RavenCommand
    {
        return new GetIndexNamesCommand($this->start, $this->pageSize);
    }
}

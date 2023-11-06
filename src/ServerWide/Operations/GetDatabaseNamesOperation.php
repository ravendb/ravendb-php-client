<?php

namespace RavenDB\ServerWide\Operations;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Http\RavenCommand;

class GetDatabaseNamesOperation implements ServerOperationInterface
{
    private int $start;
    private int $pageSize;

    public function __construct(int $start = 0, int $pageSize = 20)
    {
        $this->start = $start;
        $this->pageSize = $pageSize;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new GetDatabaseNamesCommand($this->start, $this->pageSize);
    }
}

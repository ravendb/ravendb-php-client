<?php

namespace RavenDB\ServerWide\Operations;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Http\RavenCommand;

class AddDatabaseNodeOperation implements ServerOperationInterface
{
    private ?string $databaseName = null;
    private ?string $node = null;

    public function __construct(?string $databaseName, ?string $node = null)
    {
        $this->databaseName = $databaseName;
        $this->node = $node;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new AddDatabaseNodeCommand($this->databaseName, $this->node);
    }

}

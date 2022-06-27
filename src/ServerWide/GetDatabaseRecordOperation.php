<?php

namespace RavenDB\ServerWide;

use RavenDB\Http\RavenCommand;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\ServerWide\Operations\ServerOperationInterface;

class GetDatabaseRecordOperation implements ServerOperationInterface
{
    private ?string $database = null;

    public function __construct(?string $database)
    {
        $this->database = $database;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new GetDatabaseRecordCommand($this->database);
    }
}

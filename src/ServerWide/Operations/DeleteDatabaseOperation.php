<?php

namespace RavenDB\ServerWide\Operations;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Http\RavenCommand;

class DeleteDatabaseOperation implements ServerOperationInterface
{

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        // TODO: Implement getCommand() method.
    }
}

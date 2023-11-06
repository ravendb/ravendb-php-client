<?php

namespace RavenDB\ServerWide\Operations;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Http\RavenCommand;

class GetBuildNumberOperation implements ServerOperationInterface
{
    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new GetBuildNumberCommand();
    }
}

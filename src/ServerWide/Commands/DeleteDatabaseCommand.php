<?php

namespace RavenDB\ServerWide\Commands;

use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;

class DeleteDatabaseCommand extends RavenCommand
{

    protected function createUrl(ServerNode $serverNode): string
    {
        // TODO: Implement createUrl() method.
    }
}

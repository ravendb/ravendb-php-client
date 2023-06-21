<?php

namespace RavenDB\ServerWide\Operations\Logs;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Http\RavenCommand;
use RavenDB\ServerWide\Operations\ServerOperationInterface;

class GetLogsConfigurationOperation implements ServerOperationInterface
{
    public function getCommand(?DocumentConventions $conventions): RavenCommand
    {
        return new GetLogsConfigurationCommand();
    }
}

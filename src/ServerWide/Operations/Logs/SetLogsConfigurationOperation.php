<?php

namespace RavenDB\ServerWide\Operations\Logs;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\ServerWide\Operations\VoidServerOperationInterface;

class SetLogsConfigurationOperation implements VoidServerOperationInterface
{
    private ?SetLogsConfigurationParameters $parameters = null;

    public function __construct(?SetLogsConfigurationParameters $parameters)
    {
        if ($parameters == null) {
            throw new IllegalArgumentException('Parameters cannot be null');
        }

        $this->parameters = $parameters;
    }

    public function getCommand(DocumentConventions $conventions): VoidRavenCommand
    {
        return new SetLogsConfigurationCommand($this->parameters);
    }
}

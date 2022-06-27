<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Http\RavenCommand;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\ServerWide\Operations\ServerOperationInterface;

class ToggleDatabasesStateOperation implements ServerOperationInterface
{
    private bool $disable = false;
    private ?ToggleDatabasesStateParameters $parameters = null;

    public function __construct(?string $databaseName, bool $disable = false)
    {
        if ($databaseName == null) {
            throw new IllegalArgumentException("DatabaseName cannot be null");
        }

        $this->disable = $disable;
        $this->parameters = new ToggleDatabasesStateParameters();
        $this->parameters->setDatabaseNames([ $databaseName ]);
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new ToggleDatabaseStateCommand($this->parameters, $this->disable);
    }
}

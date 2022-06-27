<?php

namespace RavenDB\ServerWide\Operations\OngoingTasks;

use RavenDB\Http\VoidRavenCommand;
use RavenDB\ServerWide\DatabaseLockMode;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\ServerWide\Operations\VoidServerOperationInterface;

class SetDatabasesLockOperation implements VoidServerOperationInterface
{
    private ?SetDatabasesLockParameters $parameters = null;

    /**
     * @param string|null|SetDatabasesLockParameters $databaseNameOrParameters
     * @param DatabaseLockMode|null $mode
     */
    public function __construct($databaseNameOrParameters = null, ?DatabaseLockMode $mode = null)
    {
        if (!$databaseNameOrParameters instanceof SetDatabasesLockParameters) {
            $this->initWithDatabaseName($databaseNameOrParameters, $mode);
            return;
        }

        $this->initWithParameters($databaseNameOrParameters);
    }

    private function initWithDatabaseName(?string $databaseName, ?DatabaseLockMode $mode = null): void
    {
        if ($databaseName == null) {
            throw new IllegalArgumentException('DatabaseName cannot be null');
        }

        $this->parameters = new SetDatabasesLockParameters();
        $this->parameters->setDatabaseNames([$databaseName]);
        $this->parameters->setMode($mode);
    }

    private function initWithParameters(?SetDatabasesLockParameters $parameters): void
    {
        if ($parameters == null) {
            throw new IllegalArgumentException('Parameters cannot be null');
        }

        if ($parameters->getDatabaseNames() == null || count($parameters->getDatabaseNames()) == 0) {
            throw new IllegalArgumentException("DatabaseNames cannot be null or empty");
        }

        $this->parameters = $parameters;
    }

    public function getCommand(DocumentConventions $conventions): VoidRavenCommand
    {
        return new SetDatabasesLockCommand($conventions, $this->parameters);
    }
}

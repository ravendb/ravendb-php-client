<?php

namespace RavenDB\ServerWide\Operations;

use DateInterval;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Http\RavenCommand;
use RavenDB\Type\StringArray;

// !status = DONE
class DeleteDatabasesOperation implements ServerOperationInterface
{
    private DeleteDatabaseCommandParameters $parameters;

    /**
     * @param DeleteDatabaseCommandParameters|string|null                   $databaseNameOrParameters
     * @param bool               $hardDelete
     * @param string             $fromNode
     * @param DateInterval|null $timeToWaitForConfirmation
     */
    public function __construct($databaseNameOrParameters, bool $hardDelete = false, string $fromNode = '', ?DateInterval $timeToWaitForConfirmation = null)
    {
        if (!$databaseNameOrParameters instanceof DeleteDatabaseCommandParameters) {
            $this->initWithDatabaseName($databaseNameOrParameters, $hardDelete, $fromNode, $timeToWaitForConfirmation);
            return;
        }

        $this->initWithParameters($databaseNameOrParameters);
    }

    public function initWithDatabaseName($databaseName, bool $hardDelete = false, string $fromNode = '', ?DateInterval $timeToWaitForConfirmation = null): void
    {
        $parameters = new DeleteDatabaseCommandParameters();

        $databases = new StringArray();
        $databases->append($databaseName);
        $parameters->setDatabaseNames($databases);

        $parameters->setHardDelete($hardDelete);
        $parameters->setTimeToWaitForConfirmation($timeToWaitForConfirmation);

        if (!empty($fromNode)) {
            $nodes = new StringArray();
            $nodes->append($fromNode);
            $parameters->setFromNodes($nodes);
        }

        $this->parameters = $parameters;
    }

    public function initWithParameters(?DeleteDatabaseCommandParameters $parameters): void
    {
        if ($parameters == null) {
            throw new IllegalArgumentException("Parameters cannot be null");
        }

        if ($parameters->getDatabaseNames() == null || count($parameters->getDatabaseNames()) == 0) {
            throw new IllegalArgumentException("Database names cannot be null");
        }

        $this->parameters = $parameters;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new DeleteDatabaseCommand($conventions, $this->parameters);
    }
}

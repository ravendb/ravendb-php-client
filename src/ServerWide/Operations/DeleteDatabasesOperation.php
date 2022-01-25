<?php

namespace RavenDB\ServerWide\Operations;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Http\RavenCommand;
use RavenDB\ServerWide\Commands\DeleteDatabaseCommand;
use RavenDB\Type\StringArray;

// !status = DONE
class DeleteDatabasesOperation implements ServerOperationInterface
{
   private DeleteDatabaseCommandParameters $parameters;

    public function __construct(string $databaseName, bool $hardDelete, string $fromNode = '', ?\DateInterval $timeToWaitForConfirmation = null)
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

//    public DeleteDatabasesOperation(Parameters parameters) {
//        if (parameters == null) {
//            throw new IllegalArgumentException("Parameters cannot be null");
//        }
//
//        if (parameters.getDatabaseNames() == null || parameters.getDatabaseNames().length == 0) {
//            throw new IllegalArgumentException("Database names cannot be null");
//        }
//
//        this.parameters = parameters;
//    }


    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new DeleteDatabaseCommand($conventions, $this->parameters);
    }
}

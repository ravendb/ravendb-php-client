<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Indexes\IndexPriority;
use RavenDB\Documents\Operations\VoidMaintenanceOperationInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\Type\StringArray;

class SetIndexesPriorityOperation implements VoidMaintenanceOperationInterface
{
    private IndexPriorityParameters $parameters;

    /**
     * @param mixed ...$parameters
     */
    public function __construct(...$parameters)
    {
        if (!count($parameters)) {
            throw new IllegalArgumentException('IndexName cannot be null');
        }

        if ($parameters[0] == null) {
            throw new IllegalArgumentException('IndexName cannot be null');
        }

        if (is_string($parameters[0])) {
            $priority = null;
            if ((count($parameters) > 1) && ($parameters[1] instanceof IndexPriority)) {
                $priority = $parameters[1];
            }

            $this->parameters = new IndexPriorityParameters();
            $this->parameters->setPriority($priority);
            $this->parameters->setIndexNames(StringArray::fromArray([$parameters[0]]));
            return;
        }

        if ($parameters[0] instanceof IndexPriorityParameters) {
            if (empty($parameters[0]->getIndexNames())) {
                throw new IllegalArgumentException('IndexNames cannot be null or empty');
            }

            $this->parameters = $parameters[0];
        }

        throw new IllegalArgumentException('Class instantiated with illegal arguments.');
    }

    function getCommand(DocumentConventions $conventions): VoidRavenCommand
    {
        return new SetIndexPriorityCommand($conventions, $this->parameters);
    }
}

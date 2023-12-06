<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Indexes\IndexLockMode;
use RavenDB\Documents\Operations\VoidMaintenanceOperationInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\Type\StringArray;

class SetIndexesLockOperation implements VoidMaintenanceOperationInterface
{
    private IndexLockParameters $parameters;

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
            $mode = null;
            if ((count($parameters) > 1) && ($parameters[1] instanceof IndexLockMode)) {
                $mode = $parameters[1];
            }

            $this->parameters = new IndexLockParameters();
            $this->parameters->setMode($mode);
            $this->parameters->setIndexNames(StringArray::fromArray([$parameters[0]]));

            $this->filterAutoIndexes();
            return;
        }

        if ($parameters[0] instanceof IndexLockParameters) {
            if (empty($parameters[0]->getIndexNames())) {
                throw new IllegalArgumentException('IndexNames cannot be null or empty');
            }

            $this->parameters = $parameters[0];
            $this->filterAutoIndexes();
            return;
        }

        throw new IllegalArgumentException('Class instantiated with illegal arguments.');

    }

    private function filterAutoIndexes(): void
    {
        // Check for auto-indexes - we do not set lock for auto-indexes
        foreach ($this->parameters->getIndexNames() as $indexName) {
            if (str_starts_with(strtolower($indexName), 'auto/')) {
                throw new IllegalArgumentException("Indexes list contains Auto-Indexes. Lock Mode is not set for Auto-Indexes.");
            }
        }
    }

    function getCommand(DocumentConventions $conventions): VoidRavenCommand
    {
        return new SetIndexLockCommand($conventions, $this->parameters);
    }
}

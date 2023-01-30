<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\DocumentStoreBase;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Operations\Indexes\PutIndexesOperation;

abstract class AbstractIndexCreationTaskBase extends AbstractCommonApiForIndexes implements AbstractIndexCreationTaskInterface
{
    /**
     * Creates the index definition.
     * @return IndexDefinition Index definition
     */
    public abstract function createIndexDefinition(): IndexDefinition;

    protected ?DocumentConventions $conventions = null;

    protected ?IndexPriority $priority = null;
    protected ?IndexLockMode $lockMode = null;

    protected ?IndexDeploymentMode $deploymentMode = null;
    protected ?IndexState $state = null;

    /**
     * Gets the conventions that should be used when index definition is created.
     * @return ?DocumentConventions $document conventions
     */
    public function getConventions(): ?DocumentConventions
    {
        return $this->conventions;
    }

    /**
     * Sets the conventions that should be used when index definition is created.
     * @param ?DocumentConventions $conventions Conventions to set
     */
    public function setConventions(?DocumentConventions $conventions): void
    {
        $this->conventions = $conventions;
    }

    public function getPriority(): ?IndexPriority
    {
        return $this->priority;
    }

    public function setPriority(?IndexPriority $priority): void
    {
        $this->priority = $priority;
    }

    public function getLockMode(): ?IndexLockMode
    {
        return $this->lockMode;
    }

    public function setLockMode(?IndexLockMode $lockMode): void
    {
        $this->lockMode = $lockMode;
    }

    public function getDeploymentMode(): ?IndexDeploymentMode
    {
        return $this->deploymentMode;
    }

    public function setDeploymentMode(?IndexDeploymentMode $deploymentMode): void
    {
        $this->deploymentMode = $deploymentMode;
    }

    public function getState(): ?IndexState
    {
        return $this->state;
    }

    public function setState(?IndexState $state): void
    {
        $this->state = $state;
    }

    /**
     * Executes the index creation against the specified document database using the specified conventions
     * @param ?DocumentStoreInterface $store target document store
     * @param ?DocumentConventions $conventions Document conventions to use
     * @param ?string $database Target database
     */
    public function execute(?DocumentStoreInterface $store, ?DocumentConventions $conventions = null, ?string $database = null): void
    {
        $oldConventions = $this->getConventions();
        $database = DocumentStoreBase::getEffectiveDatabaseForStore($store, $database);
        try {
            $this->setConventions($conventions ?? $this->getConventions() ?? $store->getRequestExecutor($database)->getConventions());

            $indexDefinition = $this->createIndexDefinition();
            $indexDefinition->setName($this->getIndexName());

            if ($this->lockMode != null) {
                $indexDefinition->setLockMode($this->lockMode);
            }

            if ($this->priority != null) {
                $indexDefinition->setPriority($this->priority);
            }

            if ($this->state != null) {
                $indexDefinition->setState($this->state);
            }

            if ($this->deploymentMode != null) {
                $indexDefinition->setDeploymentMode($this->deploymentMode);
            }

            $store->maintenance()
                    ->forDatabase($database)
                    ->send(new PutIndexesOperation($indexDefinition));
        } finally {
            $this->setConventions($oldConventions);
        }
    }
}

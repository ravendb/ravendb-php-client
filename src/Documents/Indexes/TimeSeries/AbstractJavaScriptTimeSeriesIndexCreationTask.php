<?php

namespace RavenDB\Documents\Indexes\TimeSeries;

use RavenDB\Documents\Indexes\AbstractIndexCreationTaskBase;
use RavenDB\Documents\Indexes\IndexFieldOptionsArray;
use RavenDB\Documents\Indexes\IndexType;
use RavenDB\Type\StringSet;

class AbstractJavaScriptTimeSeriesIndexCreationTask extends AbstractIndexCreationTaskBase
{
    private ?TimeSeriesIndexDefinition $definition = null;

    protected function __construct()
    {
        parent::__construct();

        $this->definition = new TimeSeriesIndexDefinition();
    }

    public function getMaps(): StringSet
    {
        return $this->definition->getMaps();
    }

    public function setMaps(StringSet|array $maps): void
    {
        if (is_array($maps)) {
            $maps = StringSet::fromArray($maps);
        }
        $this->definition->setMaps($maps);
    }

    public function getFields(): IndexFieldOptionsArray
    {
        return $this->definition->getFields();
    }

    public function setFields(IndexFieldOptionsArray|array $fields): void
    {
        if (is_array($fields)) {
            $fields = IndexFieldOptionsArray::fromArray($fields);
        }
        $this->definition->setFields($fields);
    }

    public function getReduce(): ?string
    {
        return $this->definition->getReduce();
    }

    public function setReduce(?string $reduce): void
    {
        $this->definition->setReduce($reduce);
    }

    public function isMapReduce(): bool
    {
        return $this->getReduce() != null;
    }

    /**
     * @return ?string If not null than each reduce result will be created as a document in the specified collection name.
     */
    protected function getOutputReduceToCollection(): ?string
    {
        return $this->definition->getOutputReduceToCollection();
    }

    /**
     * @param ?string $outputReduceToCollection If not null than each reduce result will be created as a document in the specified collection name.
     */
    protected function setOutputReduceToCollection(?string $outputReduceToCollection): void
    {
        $this->definition->setOutputReduceToCollection($outputReduceToCollection);
    }

    /**
     * @return ?string Defines a collection name for reference documents created based on provided pattern
     */
    protected function getPatternReferencesCollectionName(): ?string
    {
        return $this->definition->getPatternReferencesCollectionName();
    }

    /**
     * @param ?string $patternReferencesCollectionName Defines a collection name for reference documents created based on provided pattern
     */
    protected function setPatternReferencesCollectionName(?string $patternReferencesCollectionName): void
    {
        $this->definition->setPatternReferencesCollectionName($patternReferencesCollectionName);
    }

    /**
     * @return ?string Defines a collection name for reference documents created based on provided pattern
     */
    protected function getPatternForOutputReduceToCollectionReferences(): ?string
    {
        return $this->definition->getPatternForOutputReduceToCollectionReferences();
    }

    /**
     * @param ?string $patternForOutputReduceToCollectionReferences Defines a collection name for reference documents created based on provided pattern
     */
    protected function setPatternForOutputReduceToCollectionReferences(?string $patternForOutputReduceToCollectionReferences): void
    {
        $this->definition->setPatternForOutputReduceToCollectionReferences($patternForOutputReduceToCollectionReferences);
    }

    public function createIndexDefinition(): TimeSeriesIndexDefinition
    {
        $this->definition->setName($this->getIndexName());
        $this->definition->setType($this->isMapReduce() ? IndexType::javaScriptMapReduce() : IndexType::javaScriptMap());
        if ($this->getAdditionalSources() != null) {
            $this->definition->setAdditionalSources($this->getAdditionalSources());
        } else {
            $this->definition->setAdditionalSources([]);
        }
        if ($this->getAdditionalAssemblies() != null) {
            $this->definition->setAdditionalAssemblies($this->getAdditionalAssemblies());
        } else {
            $this->definition->setAdditionalAssemblies([]);
        }
        $this->definition->setConfiguration($this->getConfiguration());
        $this->definition->setLockMode($this->lockMode);
        $this->definition->setPriority($this->priority);
        $this->definition->setState($this->state);
        $this->definition->setDeploymentMode($this->deploymentMode);
        return $this->definition;
    }
}

<?php

namespace RavenDB\Documents\Indexes\Counters;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Type\StringSet;

abstract class AbstractMultiMapCountersIndexCreationTask extends AbstractGenericCountersIndexCreationTask
{
    private ?StringSet $maps = null;

    public function __construct()
    {
        parent::__construct();

        $this->maps = new StringSet();
    }

    protected function addMap(?string $map): void
    {
        if ($map == null) {
            throw new IllegalArgumentException("Map cannot be null");
        }
        $this->maps[] = $map;
    }

    public function createIndexDefinition(): CountersIndexDefinition
    {
        if ($this->conventions == null) {
            $this->conventions = new DocumentConventions();
        }

        $indexDefinitionBuilder = new CountersIndexDefinitionBuilder($this->getIndexName());
        $indexDefinitionBuilder->setIndexesStrings($this->indexesStrings);
        $indexDefinitionBuilder->setAnalyzersStrings($this->analyzersStrings);
        $indexDefinitionBuilder->setReduce($this->reduce);
        $indexDefinitionBuilder->setStoresStrings($this->storesStrings);
        $indexDefinitionBuilder->setSuggestionsOptions($this->indexSuggestions);
        $indexDefinitionBuilder->setTermVectorsStrings($this->termVectorsStrings);
        $indexDefinitionBuilder->setSpatialIndexesStrings($this->spatialOptionsStrings);
        $indexDefinitionBuilder->setOutputReduceToCollection($this->outputReduceToCollection);
        $indexDefinitionBuilder->setPatternForOutputReduceToCollectionReferences($this->patternForOutputReduceToCollectionReferences);
        $indexDefinitionBuilder->setPatternReferencesCollectionName($this->patternReferencesCollectionName);
        $indexDefinitionBuilder->setAdditionalSources($this->getAdditionalSources());
        $indexDefinitionBuilder->setAdditionalAssemblies($this->getAdditionalAssemblies());
        $indexDefinitionBuilder->setConfiguration($this->getConfiguration());
        $indexDefinitionBuilder->setLockMode($this->getLockMode());
        $indexDefinitionBuilder->setPriority($this->getPriority());
        $indexDefinitionBuilder->setState($this->getState());
        $indexDefinitionBuilder->setDeploymentMode($this->getDeploymentMode());

        $indexDefinition = $indexDefinitionBuilder->toIndexDefinition($this->conventions, false);
        $indexDefinition->setMaps($this->maps);

        return $indexDefinition;
    }
}

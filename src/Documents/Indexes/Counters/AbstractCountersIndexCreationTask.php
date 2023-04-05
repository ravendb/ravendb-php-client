<?php

namespace RavenDB\Documents\Indexes\Counters;

use RavenDB\Documents\Conventions\DocumentConventions;

class AbstractCountersIndexCreationTask extends AbstractGenericCountersIndexCreationTask
{
    protected ?string $map = null;

    public function getMap(): ?string
    {
        return $this->map;
    }

    public function setMap(?string $map): void
    {
        $this->map = $map;
    }

    public function createIndexDefinition(): CountersIndexDefinition
    {
        if ($this->conventions == null) {
            $this->conventions = new DocumentConventions();
        }

        $indexDefinitionBuilder = new CountersIndexDefinitionBuilder($this->getIndexName());
        $indexDefinitionBuilder->setIndexesStrings($this->indexesStrings);
        $indexDefinitionBuilder->setAnalyzersStrings($this->analyzersStrings);
        $indexDefinitionBuilder->setMap($this->map);
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

        return $indexDefinitionBuilder->toIndexDefinition($conventions);
    }
}

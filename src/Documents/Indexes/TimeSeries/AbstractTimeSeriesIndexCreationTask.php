<?php

namespace RavenDB\Documents\Indexes\TimeSeries;

use RavenDB\Documents\Conventions\DocumentConventions;

class AbstractTimeSeriesIndexCreationTask extends AbstractGenericTimeSeriesIndexCreationTask
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

    public function createIndexDefinition(): TimeSeriesIndexDefinition
    {
        if ($this->conventions == null) {
            $this->conventions = new DocumentConventions();
        }

        $indexDefinitionBuilder = new TimeSeriesIndexDefinitionBuilder($this->getIndexName());
        $indexDefinitionBuilder->setIndexesStrings($this->indexesStrings);
        $indexDefinitionBuilder->setAnalyzersStrings($this->analyzersStrings->getArrayCopy());
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
        $indexDefinitionBuilder->setLockMode($this->lockMode);
        $indexDefinitionBuilder->setPriority($this->priority);
        $indexDefinitionBuilder->setState($this->state);
        $indexDefinitionBuilder->setDeploymentMode($this->deploymentMode);

        return $indexDefinitionBuilder->toIndexDefinition($this->conventions);
    }
}

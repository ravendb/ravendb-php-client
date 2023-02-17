<?php

namespace RavenDB\Documents\Indexes\TimeSeries;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Type\StringList;

/**
 * Allow to create indexes with multiple maps
 */
class AbstractMultiMapTimeSeriesIndexCreationTask extends AbstractGenericTimeSeriesIndexCreationTask
{
    private ?StringList $maps = null;

    public function __construct()
    {
        parent::__construct();

        $this->maps = new StringList();
    }

    protected function addMap(?string $map): void
    {
        if ($map == null) {
            throw new IllegalArgumentException("Map cannot be null");
        }
        $this->maps->append($map);
    }

    public function createIndexDefinition(): TimeSeriesIndexDefinition
    {
        if ($this->conventions == null) {
            $this->conventions = new DocumentConventions();
        }

        $indexDefinitionBuilder = new TimeSeriesIndexDefinitionBuilder($this->getIndexName());
        $indexDefinitionBuilder->setIndexesStrings($this->indexesStrings);
        $indexDefinitionBuilder->setAnalyzersStrings($this->analyzersStrings->getArrayCopy());
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
        $indexDefinitionBuilder->setState($this->state);
        $indexDefinitionBuilder->setDeploymentMode($this->deploymentMode);

        $indexDefinition = $indexDefinitionBuilder->toIndexDefinition($this->conventions, false);
        $indexDefinition->setMaps($this->maps->getArrayCopy());

        return $indexDefinition;
    }
}

<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Type\StringSet;

class AbstractMultiMapIndexCreationTask extends AbstractGenericIndexCreationTask
{
    private StringSet $maps;

    public function __construct()
    {
        $this->maps = new StringSet();

        parent::__construct();
    }

    protected function addMap(string $map): void
    {
        if (empty($map)) {
            throw new IllegalArgumentException("Map cannot be null");
        }
        $this->maps->append($map);
    }

    public function createIndexDefinition(): IndexDefinition
    {
        if ($this->conventions == null) {
            $this->conventions = new DocumentConventions();
        }

        $indexDefinitionBuilder = new IndexDefinitionBuilder($this->getIndexName());
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

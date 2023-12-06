<?php

namespace RavenDB\Documents\Indexes;

use ArrayObject;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Indexes\Spatial\SpatialOptionsMap;
use RavenDB\Exceptions\Documents\Compilation\IndexCompilationException;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Type\StringArray;
use RavenDB\Type\StringSet;
use RavenDB\Utils\ClassUtils;


abstract class AbstractIndexDefinitionBuilder
{
    protected ?string $indexName = null;

    private ?string $reduce = null;

    private ?FieldStorageMap $storesStrings = null;
    private ?FieldIndexingMap $indexesStrings = null;
    private ?StringArray $analyzersStrings = null;
    private ?StringSet $suggestionsOptions = null;
    private ?FieldTermVectorMap $termVectorsStrings = null;
    private ?SpatialOptionsMap $spatialIndexesStrings = null;
    private ?IndexLockMode $lockMode = null;
    private ?IndexPriority $priority = null;
    private ?IndexState $state = null;
    private ?IndexDeploymentMode $deploymentMode = null;
    private ?string $outputReduceToCollection = null;
    private ?string $patternForOutputReduceToCollectionReferences = null;
    private ?string $patternReferencesCollectionName = null;
    private ?AdditionalSourcesArray $additionalSources = null;
    private ?AdditionalAssemblySet $additionalAssemblies = null;
    private ?IndexConfiguration $configuration = null;

    protected function __construct(?string $indexName = null)
    {
        $this->indexName = $indexName ?? ClassUtils::getSimpleClassName(get_class($this));
        if (strlen($this->indexName) > 256) {
            throw new IllegalArgumentException("The index name is limited to 256 characters, but was: " . $this->indexName);
        }
        $this->storesStrings = new FieldStorageMap();
        $this->indexesStrings = new FieldIndexingMap();
        $this->suggestionsOptions = new StringSet();
        $this->analyzersStrings = new StringArray();
        $this->termVectorsStrings = new FieldTermVectorMap();
        $this->spatialIndexesStrings = new SpatialOptionsMap();
        $this->configuration = new IndexConfiguration();
    }

    public function toIndexDefinition(?DocumentConventions $conventions, bool $validateMap = true): IndexDefinition
    {
        try {
            $indexDefinition = $this->newIndexDefinition();
            $indexDefinition->setName($this->indexName);
            $indexDefinition->setReduce($this->reduce);
            $indexDefinition->setLockMode($this->lockMode);
            $indexDefinition->setPriority($this->priority);
            $indexDefinition->setDeploymentMode($this->deploymentMode);
            $indexDefinition->setState($this->state);
            $indexDefinition->setOutputReduceToCollection($this->outputReduceToCollection);
            $indexDefinition->setPatternForOutputReduceToCollectionReferences($this->patternForOutputReduceToCollectionReferences);
            $indexDefinition->setPatternReferencesCollectionName($this->patternReferencesCollectionName);

            $suggestions = new ArrayObject();
            foreach ($this->suggestionsOptions as $suggestionsOption) {
                $suggestions->offsetSet($suggestionsOption, true);
            }

            $this->applyValues($indexDefinition, $this->indexesStrings, function ($options, $value) {
                $options->setIndexing($value);
            });
            $this->applyValues($indexDefinition, $this->storesStrings, function ($options, $value) {
                $options->setStorage($value);
            });
            $this->applyValues($indexDefinition, $this->analyzersStrings, function ($options, $value) {
                $options->setAnalyzer($value);
            });
            $this->applyValues($indexDefinition, $this->termVectorsStrings, function ($options, $value) {
                $options->setTermVector($value);
            });
            $this->applyValues($indexDefinition, $this->spatialIndexesStrings, function ($options, $value) {
                $options->setSpatial($value);
            });
            $this->applyValues($indexDefinition, $suggestions, function ($options, $value) {
                $options->setSuggestions($value);
            });

            $indexDefinition->setAdditionalSources($this->additionalSources);
            $indexDefinition->setAdditionalAssemblies($this->additionalAssemblies);
            $indexDefinition->setConfiguration($this->configuration);

            $this->addToIndexDefinition($indexDefinition, $conventions);

            return $indexDefinition;
        } catch (\Throwable $e) {
            throw new IndexCompilationException("Failed to create index " . $this->indexName, $e);
        }
    }

    protected abstract function newIndexDefinition(): IndexDefinition;

    protected abstract function addToIndexDefinition(IndexDefinition $indexDefinition, ?DocumentConventions $conventions): void;

    private function applyValues(IndexDefinition $indexDefinition, ArrayObject $values, /*BiConsumer<IndexFieldOptions, T>*/ \Closure $action): void
    {
        $fields = $indexDefinition->getFields();

        foreach ($values as $key => $value) {
            if (!$fields->offsetExists($key)) {
                $fields->offsetSet($key, new IndexFieldOptions());
            }
            $field = $fields[$key];
            $action($field, $value);
        }
        $indexDefinition->setFields($fields);
    }

    public function getIndexName(): ?string
    {
        return $this->indexName;
    }

    public function setIndexName(?string $indexName): void
    {
        $this->indexName = $indexName;
    }

    public function getReduce(): ?string
    {
        return $this->reduce;
    }

    public function setReduce(null|string $reduce): void
    {
        $this->reduce = $reduce;
    }

    public function getStoresStrings(): ?FieldStorageMap
    {
        return $this->storesStrings;
    }

    public function setStoresStrings(?FieldStorageMap $storesStrings): void
    {
        $this->storesStrings = $storesStrings;
    }

    public function getIndexesStrings(): ?FieldIndexingMap
    {
        return $this->indexesStrings;
    }

    public function setIndexesStrings(?FieldIndexingMap $indexesStrings): void
    {
        $this->indexesStrings = $indexesStrings;
    }

    public function getAnalyzersStrings(): ?StringArray
    {
        return $this->analyzersStrings;
    }

    public function setAnalyzersStrings(null|StringArray|array $analyzersStrings): void
    {
        if (is_array($analyzersStrings)) {
            $analyzersStrings = StringArray::fromArray($analyzersStrings);
        }
        $this->analyzersStrings = $analyzersStrings;
    }

    public function getSuggestionsOptions(): ?StringSet
    {
        return $this->suggestionsOptions;
    }

    public function setSuggestionsOptions(null|StringSet|array $suggestionsOptions): void
    {
        if (is_array($suggestionsOptions)) {
            $suggestionsOptions = StringSet::fromArray($suggestionsOptions);
        }
        $this->suggestionsOptions = $suggestionsOptions;
    }

    public function getTermVectorsStrings(): ?FieldTermVectorMap
    {
        return $this->termVectorsStrings;
    }

    public function setTermVectorsStrings(?FieldTermVectorMap $termVectorsStrings): void
    {
        $this->termVectorsStrings = $termVectorsStrings;
    }

    public function getSpatialIndexesStrings(): ?SpatialOptionsMap
    {
        return $this->spatialIndexesStrings;
    }

    public function setSpatialIndexesStrings(?SpatialOptionsMap $spatialIndexesStrings): void
    {
        $this->spatialIndexesStrings = $spatialIndexesStrings;
    }

    public function getLockMode(): ?IndexLockMode
    {
        return $this->lockMode;
    }

    public function setLockMode(?IndexLockMode $lockMode): void
    {
        $this->lockMode = $lockMode;
    }

    public function getPriority(): ?IndexPriority
    {
        return $this->priority;
    }

    public function setPriority(?IndexPriority $priority): void
    {
        $this->priority = $priority;
    }

    public function getState(): ?IndexState
    {
        return $this->state;
    }

    public function setState(?IndexState $state): void
    {
        $this->state = $state;
    }

    public function getDeploymentMode(): ?IndexDeploymentMode
    {
        return $this->deploymentMode;
    }

    public function setDeploymentMode(?IndexDeploymentMode $deploymentMode): void
    {
        $this->deploymentMode = $deploymentMode;
    }

    public function getOutputReduceToCollection(): ?string
    {
        return $this->outputReduceToCollection;
    }

    public function setOutputReduceToCollection(?string $outputReduceToCollection): void
    {
        $this->outputReduceToCollection = $outputReduceToCollection;
    }

    public function getPatternForOutputReduceToCollectionReferences(): ?string
    {
        return $this->patternForOutputReduceToCollectionReferences;
    }

    public function setPatternForOutputReduceToCollectionReferences(?string $patternForOutputReduceToCollectionReferences): void
    {
        $this->patternForOutputReduceToCollectionReferences = $patternForOutputReduceToCollectionReferences;
    }

    public function getPatternReferencesCollectionName(): ?string
    {
        return $this->patternReferencesCollectionName;
    }

    public function setPatternReferencesCollectionName(?string $patternReferencesCollectionName): void
    {
        $this->patternReferencesCollectionName = $patternReferencesCollectionName;
    }

    public function getAdditionalSources(): ?StringArray
    {
        return $this->additionalSources;
    }

    public function setAdditionalSources(null|AdditionalSourcesArray|array $additionalSources): void
    {
        $this->additionalSources = is_array($additionalSources) ? AdditionalSourcesArray::fromArray($additionalSources) : $additionalSources;
    }

    public function getAdditionalAssemblies(): ?AdditionalAssemblySet
    {
        return $this->additionalAssemblies;
    }

    public function setAdditionalAssemblies(?AdditionalAssemblySet $additionalAssemblies): void
    {
        $this->additionalAssemblies = $additionalAssemblies;
    }

    public function getConfiguration(): ?IndexConfiguration
    {
        return $this->configuration;
    }

    public function setConfiguration(?IndexConfiguration $configuration): void
    {
        $this->configuration = $configuration;
    }
}

<?php

namespace RavenDB\Documents\Indexes\Counters;

use Closure;
use RavenDB\Constants\DocumentsIndexingFields;
use RavenDB\Documents\Indexes\AbstractIndexCreationTaskBase;
use RavenDB\Documents\Indexes\FieldIndexing;
use RavenDB\Documents\Indexes\FieldIndexingMap;
use RavenDB\Documents\Indexes\FieldStorage;
use RavenDB\Documents\Indexes\FieldStorageMap;
use RavenDB\Documents\Indexes\FieldTermVector;
use RavenDB\Documents\Indexes\FieldTermVectorMap;
use RavenDB\Documents\Indexes\Spatial\SpatialOptionsFactory;
use RavenDB\Documents\Indexes\Spatial\SpatialOptionsMap;
use RavenDB\Type\StringArray;
use RavenDB\Type\StringSet;

abstract class AbstractGenericCountersIndexCreationTask extends AbstractIndexCreationTaskBase
{
    protected ?string $reduce = null;

    protected ?FieldStorageMap $storesStrings = null;
    protected ?FieldIndexingMap $indexesStrings = null;
    protected ?StringArray $analyzersStrings = null;
    protected ?StringSet $indexSuggestions = null;
    protected ?FieldTermVectorMap $termVectorsStrings = null;
    protected ?SpatialOptionsMap $spatialOptionsStrings = null;

    protected ?string $outputReduceToCollection = null;
    protected ?string $patternForOutputReduceToCollectionReferences = null;
    protected ?string $patternReferencesCollectionName = null;

    public function __construct()
    {
        parent::__construct();
        $this->storesStrings = new FieldStorageMap();
        $this->indexesStrings= new FieldIndexingMap();
        $this->analyzersStrings = new StringArray();
        $this->indexSuggestions = new StringSet();
        $this->termVectorsStrings = new FieldTermVectorMap();
        $this->spatialOptionsStrings = new SpatialOptionsMap();
    }

    /**
     * Gets a value indicating whether this instance is map reduce index definition
     * @return bool if index is of type: Map/Reduce
     */
    public function isMapReduce(): bool
    {
        return $this->reduce != null;
    }

    // AbstractGenericIndexCreationTask

    /**
     * Register a field to be indexed
     * @param string|null $field field
     * @param FieldIndexing|null $indexing Desired field indexing type
     */
    protected function index(?string $field, ?FieldIndexing $indexing)
    {
        $this->indexesStrings[$field] = $indexing;
    }

    /**
     * Register a field to be spatially indexed
     * @param string|null $field
     * @param Closure $indexing factory for spatial options
     */
    protected function spatial(?string $field, Closure $indexing)
    {
        $this->spatialOptionsStrings[$field] = $indexing(new SpatialOptionsFactory());
    }

    protected function storeAllFields(FieldStorage $storage): void
    {
        $this->storesStrings[DocumentsIndexingFields::ALL_FIELDS] = $storage;
    }

    /**
     * Register a field to be stored
     * @param string|null $field
     * @param FieldStorage|null $storage Field storage value to use
     */
    protected function store(?string $field, ?FieldStorage $storage)
    {
        $this->storesStrings[$field] = $storage;
    }

    /**
     * Register a field to be analyzed
     * @param string|null $field
     * @param string|null $analyzer analyzer to use
     */
    protected function analyze(?string $field, ?string $analyzer)
    {
        $this->analyzersStrings[$field] = $analyzer;
    }

    /**
     * Register a field to have term vectors
     * @param string|null $field
     * @param FieldTermVector|null $termVector TermVector type
     */
    protected function termVector(?string $field, ?FieldTermVector $termVector): void
    {
        $this->termVectorsStrings[$field] = $termVector;
    }

    protected function suggestion(?string $field): void
    {
        $this->indexSuggestions[] = $field;
    }
}

<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Type\StringArray;
use RavenDB\Type\StringSet;

use Symfony\Component\Serializer\Annotation\SerializedName;

// !status: DONE
class IndexDefinition
{
    public function __constructor()
    {
        $this->configuration = new IndexConfiguration();
    }

    /** @SerializedName ("Name") */
    private ?string $name = null;

    /** @SerializedName ("Priority") */
    private ?IndexPriority $priority = null;

    /** @SerializedName ("State") */
    private ?IndexState $state = null;

    /** @SerializedName ("LockMode") */
    private ?IndexLockMode $lockMode = null;

    /** @SerializedName ("AdditionalSources") */
    private ?StringArray $additionalSources = null;

    /** @SerializedName ("AdditionalAssemblies") */
    private ?AdditionalAssemblySet $additionalAssemblies = null;

    /** @SerializedName ("Maps") */
    private ?StringSet $maps = null;

    /** @SerializedName ("Reduce") */
    private ?string $reduce = null;

    /** @SerializedName ("Fields") */
    private ?IndexFieldOptionsArray $fields = null;

    /** @SerializedName ("Configuration") */
    private ?IndexConfiguration $configuration;

    /** @SerializedName ("SourceType") */
    private ?IndexSourceType $indexSourceType = null;

    /** @SerializedName ("Type") */
    private ?IndexType $indexType = null;

    /** @SerializedName ("OutputReduceToCollection") */
    private ?string $outputReduceToCollection = null;

    /** @SerializedName ("ReduceOutputIndex") */
    private ?int $reduceOutputIndex = null;

    /** @SerializedName ("PatternForOutputReduceToCollectionReferences") */
    private ?string $patternForOutputReduceToCollectionReferences = null;

    /** @SerializedName ("PatternReferencesCollectionName") */
    private ?string $patternReferencesCollectionName = null;

    /** @SerializedName ("DeploymentMode") */
    private ?IndexDeploymentMode $deploymentMode = null;

    /**
     * This is the means by which the outside world refers to this index definition
     * @return string index name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * This is the means by which the outside world refers to this index definition
     * @param string|null $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * Priority of an index
     * @return ?IndexPriority index priority
     */
    public function getPriority(): ?IndexPriority
    {
        return $this->priority;
    }

    /**
     * Priority of an index
     * @param ?IndexPriority $priority Sets the value
     */
    public function setPriority(?IndexPriority $priority): void
    {
        $this->priority = $priority;
    }
    /**
     * State of an index
     * @return ?IndexState index state
     */

    public function getState(): ?IndexState
    {
        return $this->state;
    }

    /**
     * State of an index
     * @param ?IndexState $state index state
     */

    public function setState(?IndexState $state): void
    {
        $this->state = $state;
    }

    /**
     * Index lock mode:
     * - Unlock - all index definition changes acceptable
     * - LockedIgnore - all index definition changes will be ignored, only log entry will be created
     * - LockedError - all index definition changes will raise exception
     * @return IndexLockMode index lock mode
     */
    public function getLockMode(): ?IndexLockMode
    {
        return $this->lockMode;
    }

    /**
     * Index lock mode:
     * - Unlock - all index definition changes acceptable
     * - LockedIgnore - all index definition changes will be ignored, only log entry will be created
     * - LockedError - all index definition changes will raise exception
     * @param ?IndexLockMode $lockMode sets the value
     */
    public function setLockMode(?IndexLockMode $lockMode): void
    {
        $this->lockMode = $lockMode;
    }

    /**
     * Additional code files to be compiled with this index.
     * @return StringArray additional sources
     */

    public function getAdditionalSources(): ?StringArray
    {
        if ($this->additionalSources == null) {
            $this->additionalSources = new StringArray();
        }
        return $this->additionalSources;
    }

    /**
     * Additional code files to be compiled with this index.
     * @param StringArray|null $additionalSources
     */
    public function setAdditionalSources(?StringArray $additionalSources): void
    {
        $this->additionalSources = $additionalSources;
    }

    public function getAdditionalAssemblies(): ?AdditionalAssemblySet
    {
        if ($this->additionalAssemblies == null) {
            $this->additionalAssemblies = new AdditionalAssemblySet();
        }
        return $this->additionalAssemblies;
    }

    public function setAdditionalAssemblies(?AdditionalAssemblySet $additionalAssemblies): void
    {
        $this->additionalAssemblies = $additionalAssemblies;
    }

    /**
     * All the map functions for this index
     * @return ?StringSet index maps
     */
    public function getMaps(): ?StringSet
    {
        if ($this->maps == null) {
            $this->maps = new StringSet();
        }
        return $this->maps;
    }

    /**
     * All the map functions for this index
     * @param ?StringSet $maps Sets the value
     */
    public function setMaps(?StringSet $maps): void
    {
        $this->maps = $maps;
    }

    /**
     * Index reduce function
     * @return string reduce function
     */
    public function getReduce(): ?string
    {
        return $this->reduce;
    }

    /**
     * Index reduce function
     * @param ?string $reduce Sets the reduce function
     */
    public function setReduce(?string $reduce): void
    {
        $this->reduce = $reduce;
    }

    public function toString(): string
    {
        return $this->name;
    }

    public function getFields(): ?IndexFieldOptionsArray
    {
        if ($this->fields == null) {
            $this->fields = new IndexFieldOptionsArray();
        }
        return $this->fields;
    }

    public function setFields(?IndexFieldOptionsArray $fields): void
    {
        $this->fields = $fields;
    }

    public function getConfiguration(): ?IndexConfiguration
    {
        if ($this->configuration == null) {
            $this->configuration = new IndexConfiguration();
        }
        return $this->configuration;
    }

    public function setConfiguration(?IndexConfiguration $configuration): void
    {
        $this->configuration = $configuration;
    }

    public function getSourceType(): ?IndexSourceType
    {
        if ($this->indexSourceType == null || $this->indexSourceType->isNone()) {
            $this->indexSourceType = $this->detectStaticIndexSourceType();
        }
        return $this->indexSourceType;
    }

    public function setSourceType(?IndexSourceType $indexSourceType): void
    {
        $this->indexSourceType = $indexSourceType;
    }

    public function getType(): ?IndexType
    {
        if ($this->indexType == null || $this->indexType->isNone()) {
            $this->indexType = $this->detectStaticIndexType();
        }
        return $this->indexType;
    }

    public function setType(?IndexType $indexType): void
    {
        $this->indexType = $indexType;
    }

    public function detectStaticIndexSourceType(): IndexSourceType
    {
        if ($this->maps == null || $this->maps->isEmpty()) {
            throw new IllegalArgumentException("Index definition contains no Maps");
        }

        $sourceType = IndexSourceType::none();
        foreach ($this->maps as $map) {
            $mapSourceType = IndexDefinitionHelper::detectStaticIndexSourceType($map);
            if ($sourceType->isNone()) {
                $sourceType = $mapSourceType;
                continue;
            }

            if ($sourceType->getValue() != $mapSourceType->getValue()) {
                throw new IllegalStateException("Index definition cannot contain maps with different source types.");
            }
        }
        return $sourceType;
    }

    public function detectStaticIndexType(): IndexType
    {
        $firstMap = $this->maps->first();

        if ($firstMap == null) {
            throw new IllegalArgumentException("Index  definitions contains no Maps");
        }

        return IndexDefinitionHelper::detectStaticIndexType($firstMap, $this->getReduce());
    }

    /**
     * If not null than each reduce result will be created as a document in the specified collection name.
     * @return string true if index outputs should be saved to collection
     */
    public function getOutputReduceToCollection(): ?string {
        return $this->outputReduceToCollection;
    }

    /**
     * If not null than each reduce result will be created as a document in the specified collection name.
     * @param ?string $outputReduceToCollection Sets the value
     */
    public function setOutputReduceToCollection(?string $outputReduceToCollection): void
    {
        $this->outputReduceToCollection = $outputReduceToCollection;
    }
    /**
     * If not null then this number will be part of identifier of a created document being output of reduce function
     * @return ?int output index
     */
    public function getReduceOutputIndex(): ?int
    {
        return $this->reduceOutputIndex;
    }

    /**
     * If not null then this number will be part of identifier of a created document being output of reduce function
     * @param ?int $reduceOutputIndex output index
     */
    public function setReduceOutputIndex(?int $reduceOutputIndex): void
    {
        $this->reduceOutputIndex = $reduceOutputIndex;
    }

    /**
     * Defines pattern for identifiers of documents which reference IDs of reduce outputs documents
     * @return ?string pattern
     */
    public function getPatternForOutputReduceToCollectionReferences(): ?string
    {
        return $this->patternForOutputReduceToCollectionReferences;
    }

    /**
     * Defines pattern for identifiers of documents which reference IDs of reduce outputs documents
     * @param ?string $patternForOutputReduceToCollectionReferences pattern
     */
    public function setPatternForOutputReduceToCollectionReferences(?string $patternForOutputReduceToCollectionReferences): void
    {
        $this->patternForOutputReduceToCollectionReferences = $patternForOutputReduceToCollectionReferences;
    }

    /**
     * @return ?string Defines a collection name for reference documents created based on provided pattern
     */

    public function getPatternReferencesCollectionName(): ?string
    {
        return $this->patternReferencesCollectionName;
    }

    /**
     * @param ?string $patternReferencesCollectionName Defines a collection name for reference documents created based on provided pattern
     */
    public function setPatternReferencesCollectionName(?string $patternReferencesCollectionName): void
    {
        $this->patternReferencesCollectionName = $patternReferencesCollectionName;
    }

    public function getDeploymentMode(): ?IndexDeploymentMode
    {
        return $this->deploymentMode;
    }

    public function setDeploymentMode(?IndexDeploymentMode $deploymentMode): void
    {
        $this->deploymentMode = $deploymentMode;
    }
}

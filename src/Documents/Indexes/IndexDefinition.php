<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Http\ResultInterface;
use RavenDB\Type\StringArray;
use RavenDB\Type\StringSet;

use Symfony\Component\Serializer\Annotation\SerializedName;

class IndexDefinition extends IndexDefinitionBase implements ResultInterface
{
    public function __constructor()
    {
        parent::__construct();

        $this->configuration = new IndexConfiguration();
    }

    /** @SerializedName ("LockMode") */
    private ?IndexLockMode $lockMode = null;

    /** @SerializedName ("AdditionalSources") */
    private ?AdditionalSourcesArray $additionalSources = null;

    /** @SerializedName ("AdditionalAssemblies") */
    private ?AdditionalAssemblySet $additionalAssemblies = null;

    /** @SerializedName ("Maps") */
    private ?StringSet $maps = null;

    /** @SerializedName ("Reduce") */
    private ?string $reduce = null;

    /** @SerializedName ("Fields") */
    private ?IndexFieldOptionsArray $fields = null;

    /** @SerializedName ("Configuration") */
    private ?IndexConfiguration $configuration = null;

    /** @SerializedName ("SourceType") */
    private ?IndexSourceType $sourceType = null;

    /** @SerializedName ("Type") */
    private ?IndexType $type = null;

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
     * Index lock mode:
     * - Unlock - all index definition changes acceptable
     * - LockedIgnore - all index definition changes will be ignored, only log entry will be created
     * - LockedError - all index definition changes will raise exception
     * @return ?IndexLockMode index lock mode
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
     * @return ?AdditionalSourcesArray additional sources
     */

    public function getAdditionalSources(): ?AdditionalSourcesArray
    {
        if ($this->additionalSources == null) {
            $this->additionalSources = new AdditionalSourcesArray();
        }
        return $this->additionalSources;
    }

    /**
     * Additional code files to be compiled with this index.
     * @param AdditionalSourcesArray|array|null $additionalSources
     */
    public function setAdditionalSources(null|array|AdditionalSourcesArray $additionalSources): void
    {
        if (is_array($additionalSources)) {
            $additionalSources =  AdditionalSourcesArray::fromArray($additionalSources);
        }
        $this->additionalSources = $additionalSources;
    }

    public function getAdditionalAssemblies(): ?AdditionalAssemblySet
    {
        if ($this->additionalAssemblies == null) {
            $this->additionalAssemblies = new AdditionalAssemblySet();
        }
        return $this->additionalAssemblies;
    }

    /**
     * @param AdditionalAssemblySet|array|null $additionalAssemblies
     */
    public function setAdditionalAssemblies(AdditionalAssemblySet|array|null $additionalAssemblies): void
    {
        $this->additionalAssemblies = is_array($additionalAssemblies) ? AdditionalAssemblySet::fromArray($additionalAssemblies) : $additionalAssemblies;
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
     * @param array|StringSet|null $maps Sets the value
     */
    public function setMaps(array|StringSet|null $maps): void
    {
        if (is_array($maps)) {
            $maps = StringSet::fromArray($maps);
        }
        $this->maps = $maps;
    }

    /**
     * Index reduce function
     * @return ?string reduce function
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
        return $this->getName();
    }

    public function getFields(): ?IndexFieldOptionsArray
    {
        if ($this->fields == null) {
            $this->fields = new IndexFieldOptionsArray();
        }
        return $this->fields;
    }

    public function setFields(null|IndexFieldOptionsArray|array $fields): void
    {
        if (is_array($fields)) {
            $fields = IndexFieldOptionsArray::fromArray($fields);
        }
        $this->fields = $fields;
    }

    public function & getConfiguration(): ?IndexConfiguration
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
        if ($this->sourceType == null || $this->sourceType->isNone()) {
            $this->sourceType = $this->detectStaticIndexSourceType();
        }
        return $this->sourceType;
    }

    public function setSourceType(?IndexSourceType $indexSourceType): void
    {
        $this->sourceType = $indexSourceType;
    }

    public function getType(): ?IndexType
    {
        if ($this->type == null || $this->type->isNone()) {
            $this->type = $this->detectStaticIndexType();
        }
        return $this->type;
    }

    public function setType(?IndexType $indexType): void
    {
        $this->type = $indexType;
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
     * @return ?string true if index outputs should be saved to collection
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

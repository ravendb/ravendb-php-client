<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Type\StringArray;
use RavenDB\Utils\ClassUtils;

abstract class AbstractCommonApiForIndexes
{
    private ?AdditionalSourcesArray $additionalSources = null;
    private ?AdditionalAssemblySet $additionalAssemblies = null;
    private IndexConfiguration $configuration;

    protected function __construct()
    {
        $this->configuration = new IndexConfiguration();
    }

    /**
     * Gets a value indicating whether this instance is map reduce index definition
     *
     * @return bool true if index is map reduce
     */
    public function isMapReduce(): bool
    {
        return false;
    }

    /**
     * Generates index name from type name replacing all _ with /
     *
     * @return string index name
     */
    public function getIndexName(): string
    {
        $simpleClassName = ClassUtils::getSimpleClassName(get_class($this));
        return str_replace('_', '/', $simpleClassName);
    }

    public function getAdditionalSources(): ?AdditionalSourcesArray
    {
        return $this->additionalSources;
    }

    public function setAdditionalSources(null|AdditionalSourcesArray|array $additionalSources): void
    {
        if (is_array($additionalSources)) {
            $additionalSources = AdditionalSourcesArray::fromArray($additionalSources);
        }
        $this->additionalSources = $additionalSources;
    }

    public function getAdditionalAssemblies(): ?AdditionalAssemblySet
    {
        return $this->additionalAssemblies;
    }

    public function setAdditionalAssemblies(?AdditionalAssemblySet $additionalAssemblies): void
    {
        $this->additionalAssemblies = $additionalAssemblies;
    }

    public function getConfiguration(): IndexConfiguration
    {
        return $this->configuration;
    }

    public function setConfiguration(IndexConfiguration $configuration): void
    {
        $this->configuration = $configuration;
    }
}

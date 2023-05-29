<?php

namespace RavenDB\Documents\Operations\Revisions;

use Symfony\Component\Serializer\Annotation\SerializedName;

class RevisionsConfiguration
{
    #[SerializedName('Default')]
    private ?RevisionsCollectionConfiguration $defaultConfig = null;

    #[SerializedName('Collections')]
    private ?RevisionsCollectionConfigurationArray $collections = null;

    public function getDefaultConfig(): ?RevisionsCollectionConfiguration
    {
        return $this->defaultConfig;
    }

    public function setDefaultConfig(?RevisionsCollectionConfiguration $defaultConfig): void
    {
        $this->defaultConfig = $defaultConfig;
    }

    public function getCollections(): ?RevisionsCollectionConfigurationArray
    {
        return $this->collections;
    }

    public function setCollections(null|RevisionsCollectionConfigurationArray|array $collections): void
    {
        $this->collections = is_array($collections) ? RevisionsCollectionConfigurationArray::fromArray($collections) : $collections;
    }
}

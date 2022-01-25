<?php

namespace RavenDB\Documents\Operations\Revisions;

// !status: DONE
class RevisionsConfiguration
{
    private RevisionsCollectionConfiguration $defaultConfig;

    private RevisionsCollectionConfigurationArray $collections;

    public function getDefaultConfig(): RevisionsCollectionConfiguration
    {
        return $this->defaultConfig;
    }

    public function setDefaultConfig(RevisionsCollectionConfiguration $defaultConfig): void
    {
        $this->defaultConfig = $defaultConfig;
    }

    public function getCollections(): RevisionsCollectionConfigurationArray
    {
        return $this->collections;
    }

    public function setCollections(RevisionsCollectionConfigurationArray $collections): void
    {
        $this->collections = $collections;
    }
}

<?php

namespace RavenDB\Documents\Operations\Etl\ElasticSearch;

use RavenDB\Documents\Operations\Etl\EtlConfiguration;
use RavenDB\Documents\Operations\Etl\EtlType;

class ElasticSearchEtlConfiguration extends EtlConfiguration
{
    private ?ElasticSearchIndexList $elasticIndexes = null;

    public function __construct()
    {
        parent::__construct();
        $this->elasticIndexes = new ElasticSearchIndexList();
    }

    public function getElasticIndexes(): ?ElasticSearchIndexList
    {
        return $this->elasticIndexes;
    }

    public function setElasticIndexes(?ElasticSearchIndexList $elasticIndexes): void
    {
        $this->elasticIndexes = $elasticIndexes;
    }

    public function getEtlType(): EtlType
    {
        return EtlType::elasticSearch();
    }
}

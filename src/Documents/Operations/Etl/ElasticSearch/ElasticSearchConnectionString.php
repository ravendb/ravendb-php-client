<?php

namespace RavenDB\Documents\Operations\Etl\ElasticSearch;

use RavenDB\Documents\Operations\Etl\ElasticSearch\Authentication\ElasticSearchAuthentication;
use RavenDB\ServerWide\ConnectionStringType;
use RavenDB\Type\StringArray;

class ElasticSearchConnectionString
{
    private ?StringArray $nodes = null;

    private ?ElasticSearchAuthentication $authentication = null;

    public function getType(): ConnectionStringType
    {
        return ConnectionStringType::elasticSearch();
    }

    public function getNodes(): ?StringArray
    {
        return $this->nodes;
    }

    public function setNodes(?StringArray $nodes): void
    {
        $this->nodes = $nodes;
    }

    public function getAuthentication(): ?ElasticSearchAuthentication
    {
        return $this->authentication;
    }

    public function setAuthentication(?ElasticSearchAuthentication $authentication): void
    {
        $this->authentication = $authentication;
    }
}

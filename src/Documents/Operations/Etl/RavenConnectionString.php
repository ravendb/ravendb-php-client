<?php

namespace RavenDB\Documents\Operations\Etl;

use RavenDB\Documents\Operations\ConnectionStrings\ConnectionString;
use RavenDB\ServerWide\ConnectionStringType;
use RavenDB\Type\UrlArray;
use Symfony\Component\Serializer\Annotation\SerializedName;

class RavenConnectionString extends ConnectionString
{
    #[SerializedName("Database")]
    private ?string $database = null;

    #[SerializedName("TopologyDiscoveryUrls")]
    private ?UrlArray $topologyDiscoveryUrls = null;

    #[SerializedName("Type")]
    private ?ConnectionStringType $type = null;

    public function __construct()
    {
        $this->type = ConnectionStringType::Raven();
    }

    public function getDatabase(): ?string
    {
        return $this->database;
    }

    public function setDatabase(?string $database): void
    {
        $this->database = $database;
    }

    public function getTopologyDiscoveryUrls(): ?UrlArray
    {
        return $this->topologyDiscoveryUrls;
    }

    public function setTopologyDiscoveryUrls(null|array|UrlArray $topologyDiscoveryUrls): void
    {
        $this->topologyDiscoveryUrls =
            is_array($topologyDiscoveryUrls) ?
                UrlArray::fromArray($topologyDiscoveryUrls) :
                $topologyDiscoveryUrls;
    }

    public function getType(): ConnectionStringType
    {
        return $this->type;
    }
}

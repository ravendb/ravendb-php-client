<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Documents\Indexes\IndexDefinitionArray;

use Symfony\Component\Serializer\Annotation\SerializedName;

class GetIndexesResponse
{
    /** @SerializedName ("Results") */
    private ?IndexDefinitionArray $results;

    public function getResults(): ?IndexDefinitionArray
    {
        return $this->results;
    }

    public function setResults(?IndexDefinitionArray $results): void
    {
        $this->results = $results;
    }
}

<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Documents\Indexes\IndexErrorsArray;
use Symfony\Component\Serializer\Annotation\SerializedName;

class GetIndexErrorsResponse
{
    /** @SerializedName ("Results") */
    private ?IndexErrorsArray $results;

    public function getResults(): ?IndexErrorsArray
    {
        return $this->results;
    }

    public function setResults(?IndexErrorsArray $results): void
    {
        $this->results = $results;
    }
}

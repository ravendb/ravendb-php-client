<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Type\StringArray;
use RavenDB\Type\StringArrayResult;

use Symfony\Component\Serializer\Annotation\SerializedName;

class StringResultResponse
{
    /** @SerializedName ("Results") */
    private ?StringArrayResult $results;

    public function getResults(): ?StringArray
    {
        return $this->results;
    }

    public function setResults(?StringArrayResult $results): void
    {
        $this->results = $results;
    }
}

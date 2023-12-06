<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Documents\Indexes\IndexStatsArray;

use Symfony\Component\Serializer\Annotation\SerializedName;

class GetIndexStatisticsResponse
{
    /** @SerializedName ("Results") */
    private ?IndexStatsArray $results;

    public function getResults(): ?IndexStatsArray
    {
        return $this->results;
    }

    public function setResults(?IndexStatsArray $results): void
    {
        $this->results = $results;
    }
}

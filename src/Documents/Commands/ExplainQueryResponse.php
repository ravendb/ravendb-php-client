<?php

namespace RavenDB\Documents\Commands;

use RavenDB\Http\ResultInterface;

use Symfony\Component\Serializer\Annotation\SerializedName;

class ExplainQueryResponse
{
    /** @SerializedName ("IndexName") */
    private ?string $indexName = null;

    /** @SerializedName ("Results") */
    private ?ExplainQueryResultArray $results = null;

    public function getIndexName(): ?string
    {
        return $this->indexName;
    }

    public function setIndexName(?string $indexName): void
    {
        $this->indexName = $indexName;
    }

    public function getResults(): ?ExplainQueryResultArray
    {
        return $this->results;
    }

    public function setResults(?ExplainQueryResultArray $results): void
    {
        $this->results = $results;
    }
}

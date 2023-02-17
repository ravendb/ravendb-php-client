<?php

namespace RavenDB\Documents\Queries\TimeSeries;

class TimeSeriesQueryBuilder implements TimeSeriesQueryBuilderInterface
{
    private ?string $query = null;

    function raw(?string $queryText): ?TimeSeriesQueryResult
    {
        $this->query = $queryText;
        return null;
    }

    public function getQueryText(): ?string
    {
        return $this->query;
    }
}

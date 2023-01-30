<?php

namespace RavenDB\Documents\Queries\TimeSeries;

interface TimeSeriesQueryBuilderInterface
{
    function raw(?string $queryText): ?TimeSeriesQueryResult;
}

<?php

namespace RavenDB\Documents\Queries\TimeSeries;

use Symfony\Component\Serializer\Annotation\SerializedName;

class TimeSeriesQueryResult
{
    #[SerializedName("Count")]
    private ?int $count = null;

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(?int $count): void
    {
        $this->count = $count;
    }
}

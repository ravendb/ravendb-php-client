<?php

namespace RavenDB\Documents\Session\TimeSeries;

use Attribute;

#[Attribute]
class TimeSeriesValue
{
    private int $idx;
    private ?string $name = null;

    public function __construct(int $idx, ?string $name = null)
    {
        $this->idx = $idx;
        $this->name = $name;
    }

    public function getIdx(): int
    {
        return $this->idx;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}

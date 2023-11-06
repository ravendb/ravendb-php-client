<?php

namespace RavenDB\Documents\Identity;

use RavenDB\Utils\AtomicInteger;

class RangeValue
{
    public int $Min;
    public int $Max;
    public AtomicInteger $Current;

    public function __construct(int $min, int $max)
    {
        $this->Min = $min;
        $this->Max = $max;
        $this->Current = new AtomicInteger($min - 1);
    }
}

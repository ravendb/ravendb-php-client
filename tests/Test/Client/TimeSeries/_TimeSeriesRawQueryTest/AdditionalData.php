<?php

namespace tests\RavenDB\Test\Client\TimeSeries\_TimeSeriesRawQueryTest;

class AdditionalData
{
    private ?NestedClass $nestedClass = null;

    public function getNestedClass(): ?NestedClass
    {
        return $this->nestedClass;
    }

    public function setNestedClass(?NestedClass $nestedClass): void
    {
        $this->nestedClass = $nestedClass;
    }
}

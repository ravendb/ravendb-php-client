<?php

namespace RavenDB\Documents\Operations\TimeSeries;

// !status = DONE
abstract class AbstractTimeSeriesRange
{
    private ?string $name = '';

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }
}

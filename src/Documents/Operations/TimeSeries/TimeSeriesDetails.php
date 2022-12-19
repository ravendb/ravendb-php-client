<?php

namespace RavenDB\Documents\Operations\TimeSeries;

class TimeSeriesDetails
{
    private ?string $id = null;
    /** @var array<TimeSeriesRangeResultList>  */
    private array $values;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function setValues(array $values): void
    {
        $this->values = $values;
    }
}

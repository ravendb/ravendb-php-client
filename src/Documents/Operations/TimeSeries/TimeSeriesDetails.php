<?php

namespace RavenDB\Documents\Operations\TimeSeries;

class TimeSeriesDetails
{
    private ?string $id = null;

    private ?TimeSeriesRangeResultListArray $values = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getValues(): ?TimeSeriesRangeResultListArray
    {
        return $this->values;
    }

    public function setValues(null|TimeSeriesRangeResultListArray|array $values): void
    {
        if (is_array($values)) {
            $values = TimeSeriesRangeResultListArray::fromArray($values);
        }
        $this->values = $values;
    }
}

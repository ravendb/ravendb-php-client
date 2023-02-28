<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Type\StringArray;
use RavenDB\Utils\StringUtils;
use Symfony\Component\Serializer\Annotation\SerializedName;

class ConfigureTimeSeriesValueNamesParameters
{
    #[SerializedName("Collection")]
    private ?string $collection = null;

    #[SerializedName("TimeSeries")]
    private ?string $timeSeries = null;

    #[SerializedName("ValueNames")]
    private ?StringArray $valueNames = null;

    #[SerializedName("Update")]
    private bool $update = false;

    public function validate(): void
    {
        if (StringUtils::isEmpty($this->collection)) {
            throw new IllegalArgumentException("Collection cannot be null or empty");
        }
        if (StringUtils::isEmpty($this->timeSeries)) {
            throw new IllegalArgumentException("TimeSeries cannot be null or empty");
        }
        if (empty($this->valueNames) || count($this->valueNames) == 0) {
            throw new IllegalArgumentException("ValuesNames cannot be null or empty");
        }
    }

    public function getCollection(): ?string
    {
        return $this->collection;
    }

    public function setCollection(?string $collection): void
    {
        $this->collection = $collection;
    }

    public function getTimeSeries(): ?string
    {
        return $this->timeSeries;
    }

    public function setTimeSeries(?string $timeSeries): void
    {
        $this->timeSeries = $timeSeries;
    }

    public function getValueNames(): ?StringArray
    {
        return $this->valueNames;
    }

    public function setValueNames(null|array|StringArray $valueNames): void
    {
        $this->valueNames = is_array($valueNames) ? StringArray::fromArray($valueNames) : $valueNames;
    }

    public function isUpdate(): bool
    {
        return $this->update;
    }

    public function setUpdate(bool $update): void
    {
        $this->update = $update;
    }
}

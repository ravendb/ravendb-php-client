<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use DateInterval;
use RavenDB\Type\StringArray;

use Symfony\Component\Serializer\Annotation\SerializedName;

// !status: DONE
class TimeSeriesConfiguration
{
    public const TIME_SERIES_ROLLUP_SEPARATOR = '@';

    /** @SerializedName("Collections") */
    private ?TimeSeriesCollectionConfigurationArray $collections = null;

    /** @SerializedName("PolicyCheckFrequency") */
    private ?DateInterval $policyCheckFrequency = null;

    /** @SerializedName("NamedValues") */
    private array $namedValues = [];

    public function __construct(
        ?TimeSeriesCollectionConfigurationArray $collections = null,
        ?DateInterval $policyCheckFrequency = null,
        array $namedValues = []
    )
    {
        $this->collections = $collections;
        $this->policyCheckFrequency = $policyCheckFrequency;
        $this->namedValues = $namedValues;

        $this->internalPostJsonDeserialization();
    }


    public function getCollections(): TimeSeriesCollectionConfigurationArray
    {
        return $this->collections;
    }

    public function setCollections(TimeSeriesCollectionConfigurationArray $collections): void
    {
        $this->collections = $collections;
    }

    public function getPolicyCheckFrequency(): DateInterval
    {
        return $this->policyCheckFrequency;
    }

    public function setPolicyCheckFrequency(DateInterval $policyCheckFrequency): void
    {
        $this->policyCheckFrequency = $policyCheckFrequency;
    }

    public function getNamedValues(): array
    {
        return $this->namedValues;
    }

    public function setNamedValues(array $namedValues): void
    {
        $this->namedValues = $namedValues;
    }

    public function getNames(string $collection, string $timeSeries): ?StringArray
    {
        if (empty($this->namedValues)) {
            return null;
        }

        if (!in_array($collection, $this->namedValues)) {
            return null;
        }
        $timeSeriesHolder = $this->namedValues[$collection];

        if (!in_array($timeSeries, $timeSeriesHolder)) {
            return null;
        }
        return $timeSeriesHolder[$timeSeries];
    }

    private function internalPostJsonDeserialization(): void
    {
        $this->populateNamedValues();
        $this->populatePolicies();
    }

    private function populatePolicies(): void
    {
        if ($this->collections == null) {
            return;
        }

        // @todo: we should test does this work as expected
        $dic = new TimeSeriesCollectionConfigurationArray();

        foreach ($this->collections as $key => $value) {
            $dic[$key] = $value;
        }
        $this->collections = $dic;
    }

    private function populateNamedValues(): void
    {
        if (empty($this->namedValues)) {
            return;
        }

        // @todo: we should test does this work as expected
        $dic = [];
        foreach ($this->namedValues as $key => $value) {
            $valueMap = [];
            $valueMap = $value;
            $dic[$key] = $valueMap;
        }

        $this->namedValues = $dic;
    }
}

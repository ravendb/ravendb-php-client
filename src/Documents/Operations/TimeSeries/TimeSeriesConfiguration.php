<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use DateInterval;
use RavenDB\Type\Duration;
use RavenDB\Type\ExtendedArrayObject;
use RavenDB\Type\StringArray;

use Symfony\Component\Serializer\Annotation\SerializedName;

class TimeSeriesConfiguration
{
    public const TIME_SERIES_ROLLUP_SEPARATOR = '@';

    /** @SerializedName("Collections") */
    private ?TimeSeriesCollectionConfigurationMap $collections = null;

    /** @SerializedName("PolicyCheckFrequency") */
    private ?Duration $policyCheckFrequency = null;

    /** @SerializedName("NamedValues") */
    private ?ExtendedArrayObject $namedValues = null;

    public function __construct(
        ?TimeSeriesCollectionConfigurationMap $collections = null,
        ?Duration                             $policyCheckFrequency = null,
        null|array                            $namedValues = null
    )
    {
        $this->collections = $collections ?? new TimeSeriesCollectionConfigurationMap();
        $this->policyCheckFrequency = $policyCheckFrequency;
        $this->namedValues = ExtendedArrayObject::ensure($namedValues);
        $this->namedValues->setKeysCaseInsensitive(true);

        $this->internalPostJsonDeserialization();
    }


    public function & getCollections(): ?TimeSeriesCollectionConfigurationMap
    {
        return $this->collections;
    }

    public function setCollections(null|TimeSeriesCollectionConfigurationMap|array $collections): void
    {
        if (is_array($collections)) {
            $collections = TimeSeriesCollectionConfigurationMap::fromArray($collections);
        }
        $this->collections = $collections;
    }

    public function getPolicyCheckFrequency(): ?Duration
    {
        return $this->policyCheckFrequency;
    }

    public function setPolicyCheckFrequency(?Duration $policyCheckFrequency): void
    {
        $this->policyCheckFrequency = $policyCheckFrequency;
    }

    public function getNamedValues(): ?array
    {
        return $this->namedValues->getArrayCopy();
    }

    public function setNamedValues(null|array $namedValues): void
    {
        $this->namedValues = ExtendedArrayObject::ensure($namedValues);
        $this->namedValues->setKeysCaseInsensitive(true);
    }

    public function getNames(string $collection, string $timeSeries): ?array
    {
        if (empty($this->namedValues)) {
            return null;
        }

        if (!$this->namedValues->offsetExists($collection)) {
            return null;
        }
        $timeSeriesHolder = array_change_key_case($this->namedValues[$collection], CASE_LOWER);

        $timeSeriesLowerCase = strtolower($timeSeries);
        if (!array_key_exists($timeSeriesLowerCase, $timeSeriesHolder)) {
            return null;
        }
        return $timeSeriesHolder[$timeSeriesLowerCase];
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
        $dic = new TimeSeriesCollectionConfigurationMap();

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

        $dic = new ExtendedArrayObject();
        $dic->setKeysCaseInsensitive(true);
        foreach ($this->namedValues as $key => $value) {
            $valueMap = [];
            $valueMap = $value;
            $dic->offsetSet($key, $valueMap);
        }

        $this->namedValues = $dic;
    }
}

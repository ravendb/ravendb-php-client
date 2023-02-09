<?php

namespace RavenDB\Documents\Session\TimeSeries;

use DateTime;

use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Primitives\NetISO8601Utils;
use Symfony\Component\Serializer\Annotation\SerializedName;

class TimeSeriesEntry
{
    /** @SerializedName( "Timestamp" )  */
    private ?DateTime $timestamp = null;

    /** @SerializedName( "Tag" )  */
    private ?string $tag = null;

    /** @SerializedName( "Values" )  */
    private ?array $values = null;

    /** @SerializedName( "IsRollup" )  */
    private bool $rollup = false;


    public function __construct(?array  $data = null)
    {
        if (!empty($data)) {
            $this->initFromArray($data);
        }
    }

    private function initFromArray(array $data): void
    {
        if (array_key_exists('Timestamp', $data)) {
            $this->timestamp = NetISO8601Utils::fromString($data['Timestamp']);
        }
        if (array_key_exists('Tag', $data)) {
            $this->tag = strval($data['Tag']);
        }
        if (array_key_exists('IsRollup', $data)) {
            $this->rollup = boolval($data['IsRollup']);
        }
        if (array_key_exists('Values', $data)) {
            $this->values = $data['Values'];
        }
    }

    public function getTimestamp(): ?DateTime
    {
        return $this->timestamp;
    }

    public function setTimestamp(?DateTime $timestamp): void
    {
        $this->timestamp = $timestamp;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function setTag(?string $tag): void
    {
        $this->tag = $tag;
    }

    public function getValues(): ?array
    {
        return $this->values;
    }

    public function setValues(?array $values): void
    {
        $this->values = $values;
    }

    public function isRollup(): bool
    {
        return $this->rollup;
    }

    public function setRollup(bool $rollup): void
    {
        $this->rollup = $rollup;
    }

//    @JsonIgnore
    public function getValue(): float
    {
        if (count($this->values) == 1) {
            return $this->values[0];
        }

        throw new IllegalStateException("Entry has more than one value.");
    }

//    @JsonIgnore
    public function setValue(float $value): void
    {
        if (count($this->values) == 1) {
            $this->values[0] = $value;
            return;
        }

        throw new IllegalStateException("Entry has more than one value.");
    }

    public function asTypedEntry(string $className): TypedTimeSeriesEntry
    {
        $entry = new TypedTimeSeriesEntry();
        $entry->setRollup($this->rollup);
        $entry->setTag($this->tag);
        $entry->setTimestamp($this->timestamp);
        $entry->setValues($this->values);
        $entry->setValue(TimeSeriesValuesHelper::setFields($className, $this->values, $this->rollup));
        return $entry;
    }
}

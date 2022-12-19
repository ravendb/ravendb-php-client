<?php

namespace RavenDB\Documents\Session\TimeSeries;

use DateTime;

use RavenDB\Exceptions\IllegalStateException;
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

//    public <T> TypedTimeSeriesEntry<T> asTypedEntry(Class<T> clazz) {
//        TypedTimeSeriesEntry<T> entry = new TypedTimeSeriesEntry<>();
//        entry.setRollup(rollup);
//        entry.setTag(tag);
//        entry.setTimestamp(timestamp);
//        entry.setValues(values);
//        entry.setValue(TimeSeriesValuesHelper.setFields(clazz, values, rollup));
//        return entry;
//    }
}

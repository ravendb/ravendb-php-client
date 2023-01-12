<?php

namespace RavenDB\Documents\Session\TimeSeries;

use DateTime;

/**
 * @template  T
 */
class TypedTimeSeriesEntry
{
    /** @SerializedName( "Timestamp" )  */
    private ?DateTime $timestamp = null;

    /** @SerializedName( "Tag" )  */
    private ?string $tag = null;

    /** @SerializedName( "Values" )  */
    private ?array $values = null;

    /** @SerializedName( "IsRollup" )  */
    private bool $rollup = false;

    /** @var ?T  */
    private mixed $value;

    /**
     * @return DateTime|null
     */
    public function getTimestamp(): ?DateTime
    {
        return $this->timestamp;
    }

    /**
     * @param DateTime|null $timestamp
     */
    public function setTimestamp(?DateTime $timestamp): void
    {
        $this->timestamp = $timestamp;
    }

    /**
     * @return string|null
     */
    public function getTag(): ?string
    {
        return $this->tag;
    }

    /**
     * @param string|null $tag
     */
    public function setTag(?string $tag): void
    {
        $this->tag = $tag;
    }

    /**
     * @return array|null
     */
    public function getValues(): ?array
    {
        return $this->values;
    }

    /**
     * @param array|null $values
     */
    public function setValues(?array $values): void
    {
        $this->values = $values;
    }

    /**
     * @return bool
     */
    public function isRollup(): bool
    {
        return $this->rollup;
    }

    /**
     * @param bool $rollup
     */
    public function setRollup(bool $rollup): void
    {
        $this->rollup = $rollup;
    }

    /**
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @param T|null $value
     */
    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }
}

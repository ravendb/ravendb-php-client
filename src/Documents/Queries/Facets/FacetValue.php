<?php

namespace RavenDB\Documents\Queries\Facets;

class FacetValue
{
    private ?string $name = null;
    private ?string $range = null;
    private ?int $count = null;
    /** @var int|float|null */
    private $sum = null;
    /** @var int|float|null */
    private $max = null;
    /** @var int|float|null */
    private $min = null;
    /** @var int|float|null */
    private $average = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getRange(): ?string
    {
        return $this->range;
    }

    public function setRange(?string $range): void
    {
        $this->range = $range;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(?int $count): void
    {
        $this->count = $count;
    }

    /**
     * @return float|int|null
     */
    public function getSum()
    {
        return $this->sum;
    }

    /**
     * @param null|float|int $sum
     */
    public function setSum($sum): void
    {
        $this->sum = $sum != null ? floatval($sum) : null;
    }

    /**
     * @return float|int|null
     */
    public function getMax()
    {
        return $this->max;
    }

    /**
     * @param null|float|int $max
     */
    public function setMax($max): void
    {
        $this->max = $max != null ? floatval($max) : null;
    }

    /**
     * @return float|int|null
     */
    public function getMin()
    {
        return $this->min;
    }

    /**
     * @param null|float|int $min
     */
    public function setMin($min): void
    {
        $this->min = $min != null ? floatval($min) : null;
    }

    /**
     * @return float|int|null
     */
    public function getAverage()
    {
        return $this->average;
    }

    /**
     * @param null|float|int $average
     */
    public function setAverage($average): void
    {
        $this->average = $average != null ? floatval($average) : null;
    }
}

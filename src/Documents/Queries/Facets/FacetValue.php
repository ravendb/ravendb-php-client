<?php

namespace RavenDB\Documents\Queries\Facets;

class FacetValue
{
    private ?string $name = null;
    private ?string $range = null;
    private ?int $count = null;
    private ?float $sum = null;
    private ?float $max = null;
    private ?float $min = null;
    private ?float $average = null;

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

    public function getSum(): ?float
    {
        return $this->sum;
    }

    public function setSum(?float $sum): void
    {
        $this->sum = $sum;
    }

    public function getMax(): ?float
    {
        return $this->max;
    }

    public function setMax(?float $max): void
    {
        $this->max = $max;
    }

    public function getMin(): ?float
    {
        return $this->min;
    }

    public function setMin(?float $min): void
    {
        $this->min = $min;
    }

    public function getAverage(): ?float
    {
        return $this->average;
    }

    public function setAverage(?float $average): void
    {
        $this->average = $average;
    }
}

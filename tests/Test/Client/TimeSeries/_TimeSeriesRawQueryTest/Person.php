<?php

namespace tests\RavenDB\Test\Client\TimeSeries\_TimeSeriesRawQueryTest;

class Person
{
    private ?string $name = null;
    private ?int $age = null;
    private ?string $worksAt = null;
    private ?string $event = null;
    private ?AdditionalData $additionalData = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(?int $age): void
    {
        $this->age = $age;
    }

    public function getWorksAt(): ?string
    {
        return $this->worksAt;
    }

    public function setWorksAt(?string $worksAt): void
    {
        $this->worksAt = $worksAt;
    }

    public function getEvent(): ?string
    {
        return $this->event;
    }

    public function setEvent(?string $event): void
    {
        $this->event = $event;
    }

    public function getAdditionalData(): ?AdditionalData
    {
        return $this->additionalData;
    }

    public function setAdditionalData(?AdditionalData $additionalData): void
    {
        $this->additionalData = $additionalData;
    }
}

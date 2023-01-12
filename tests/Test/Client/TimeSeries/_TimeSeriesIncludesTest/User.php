<?php

namespace tests\RavenDB\Test\Client\TimeSeries\_TimeSeriesIncludesTest;

class User
{
    private ?string $name = null;
    private ?string $worksAt = null;
    private ?string $id = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getWorksAt(): ?string
    {
        return $this->worksAt;
    }

    public function setWorksAt(?string $worksAt): void
    {
        $this->worksAt = $worksAt;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }
}

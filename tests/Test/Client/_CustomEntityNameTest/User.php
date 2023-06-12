<?php

namespace tests\RavenDB\Test\Client\_CustomEntityNameTest;

class User
{
    private ?string $id = null;
    private ?string $carId = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getCarId(): ?string
    {
        return $this->carId;
    }

    public function setCarId(?string $carId): void
    {
        $this->carId = $carId;
    }
}

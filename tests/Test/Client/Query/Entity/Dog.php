<?php

namespace tests\RavenDB\Test\Client\Query\Entity;

use Symfony\Component\Serializer\Annotation\SerializedName;

class Dog
{
    /** @SerializedName ("Id") */
    private ?string $id = null;
    private ?string $name = null;
    private ?string $breed = null;
    private ?string $color = null;
    private ?int $age = null;
    private bool $isVaccinated = false;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getBreed(): ?string
    {
        return $this->breed;
    }

    public function setBreed(?string $breed): void
    {
        $this->breed = $breed;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): void
    {
        $this->color = $color;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(?int $age): void
    {
        $this->age = $age;
    }

    public function isVaccinated(): bool
    {
        return $this->isVaccinated;
    }

    public function setVaccinated(bool $isVaccinated): void
    {
        $this->isVaccinated = $isVaccinated;
    }
}

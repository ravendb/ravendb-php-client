<?php

namespace tests\RavenDB\Test\Client\Query\Index;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;

class DogsIndexResult extends AbstractIndexCreationTask
{
    private ?string $name = null;
    private ?int $age = null;
    private bool $isVaccinated = false;

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

    public function isVaccinated(): bool
    {
        return $this->isVaccinated;
    }

    public function setIsVaccinated(bool $isVaccinated): void
    {
        $this->isVaccinated = $isVaccinated;
    }
}

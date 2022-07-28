<?php

namespace tests\RavenDB\Test\Client\_CrudTest\Entities;

use tests\RavenDB\Infrastructure\Entity\User;

class Poc
{
    private ?string $name = null;
    private ?User $obj = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getObj(): ?User
    {
        return $this->obj;
    }

    public function setObj(?User $obj): void
    {
        $this->obj = $obj;
    }

}

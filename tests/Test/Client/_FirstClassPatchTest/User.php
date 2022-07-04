<?php

namespace tests\RavenDB\Test\Client\_FirstClassPatchTest;

use DateTimeInterface;

class User
{
    private ?StuffArray $stuff = null;
    private ?DateTimeInterface $lastLogin = null;

    /** @var int[]  */
    private array $numbers = [];

    public function getStuff(): ?StuffArray
    {
        return $this->stuff;
    }

    public function setStuff(?StuffArray $stuff): void
    {
        $this->stuff = $stuff;
    }

    public function getLastLogin(): ?DateTimeInterface
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?DateTimeInterface $lastLogin): void
    {
        $this->lastLogin = $lastLogin;
    }

    public function & getNumbers(): array
    {
        return $this->numbers;
    }

    public function setNumbers(array $numbers): void
    {
        $this->numbers = $numbers;
    }
}

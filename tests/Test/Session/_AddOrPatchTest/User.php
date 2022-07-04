<?php

namespace tests\RavenDB\Test\Session\_AddOrPatchTest;

use DateTimeInterface;
use RavenDB\Type\DateTimeArray;

class User
{
    private ?DateTimeInterface $lastLogin = null;
    private ?string $firstName;
    private ?string $lastName;
    private ?DateTimeArray $loginTimes = null;
    private ?int $loginCount = null;

    public function getLastLogin(): ?DateTimeInterface
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?DateTimeInterface $lastLogin): void
    {
        $this->lastLogin = $lastLogin;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getLoginTimes(): ?DateTimeArray
    {
        return $this->loginTimes;
    }

    public function setLoginTimes(?DateTimeArray $loginTimes): void
    {
        $this->loginTimes = $loginTimes;
    }

    public function getLoginCount(): ?int
    {
        return $this->loginCount;
    }

    public function setLoginCount(?int $loginCount): void
    {
        $this->loginCount = $loginCount;
    }
}

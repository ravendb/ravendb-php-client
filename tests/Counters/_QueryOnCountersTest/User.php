<?php

namespace tests\RavenDB\Counters\_QueryOnCountersTest;

class User
{
    private ?string $name = null;
    private ?int $age = null;
    private ?string $friendId = null;

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

    public function getFriendId(): ?string
    {
        return $this->friendId;
    }

    public function setFriendId(?string $friendId): void
    {
        $this->friendId = $friendId;
    }
}

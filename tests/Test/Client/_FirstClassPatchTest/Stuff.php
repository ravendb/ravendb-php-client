<?php

namespace tests\RavenDB\Test\Client\_FirstClassPatchTest;

class Stuff
{
    private ?int $key = null;
    private ?string $phone = null;
    private ?Pet $pet = null;
    private ?Friend $friend = null;
    private array $dic = [];

    public function getKey(): ?int
    {
        return $this->key;
    }

    public function setKey(?int $key): void
    {
        $this->key = $key;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    public function getPet(): ?Pet
    {
        return $this->pet;
    }

    public function setPet(?Pet $pet): void
    {
        $this->pet = $pet;
    }

    public function getFriend(): ?Friend
    {
        return $this->friend;
    }

    public function setFriend(?Friend $friend): void
    {
        $this->friend = $friend;
    }

    public function getDic(): array
    {
        return $this->dic;
    }

    public function setDic(array $dic): void
    {
        $this->dic = $dic;
    }
}

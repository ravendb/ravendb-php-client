<?php

namespace tests\RavenDB\Test\MailingList\_LoadAllStartingWithTest;

class Abc
{
    private ?string $id = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }
}

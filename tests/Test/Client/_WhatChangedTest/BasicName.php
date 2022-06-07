<?php

namespace tests\RavenDB\Test\Client\_WhatChangedTest;

class BasicName
{
    private ?string $name = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }
}

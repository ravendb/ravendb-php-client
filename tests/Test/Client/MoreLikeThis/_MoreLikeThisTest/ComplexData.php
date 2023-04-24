<?php

namespace tests\RavenDB\Test\Client\MoreLikeThis\_MoreLikeThisTest;

class ComplexData
{
    private ?string $id = null;
    private ?ComplexProperty $property = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getProperty(): ?ComplexProperty
    {
        return $this->property;
    }

    public function setProperty(?ComplexProperty $property): void
    {
        $this->property = $property;
    }
}

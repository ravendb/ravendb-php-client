<?php

namespace tests\RavenDB\Test\Server\Pathcing\_AdvancedPatchingTest;

use DateTimeInterface;

class CustomType
{
    private ?string $id = null;
    private ?string $owner = null;
    private ?int $value = null;
    private ?array $comments = null;
    private ?DateTimeInterface $date = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getOwner(): ?string
    {
        return $this->owner;
    }

    public function setOwner(?string $owner): void
    {
        $this->owner = $owner;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function setValue(?int $value): void
    {
        $this->value = $value;
    }

    public function getComments(): ?array
    {
        return $this->comments;
    }

    public function setComments(?array $comments): void
    {
        $this->comments = $comments;
    }

    public function getDate(): ?DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?DateTimeInterface $date): void
    {
        $this->date = $date;
    }
}

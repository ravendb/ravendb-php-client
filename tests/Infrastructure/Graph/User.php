<?php

namespace tests\RavenDB\Infrastructure\Graph;

class User
{
    private ?string $id = null;
    private ?string $name = null;
    private ?int $age = null;

    private ?UserRatingList $hasRated = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

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

    public function getHasRated(): ?UserRatingList
    {
        return $this->hasRated;
    }

    /**
     * @param ?UserRatingList|array $hasRated
     */
    public function setHasRated($hasRated): void
    {
        if (is_array($hasRated)) {
            $hasRated = UserRatingList::fromArray($hasRated);
        }
        $this->hasRated = $hasRated;
    }
}

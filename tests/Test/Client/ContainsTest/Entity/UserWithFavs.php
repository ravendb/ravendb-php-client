<?php

namespace tests\RavenDB\Test\Client\ContainsTest\Entity;

use RavenDB\Type\StringList;

class UserWithFavs
{
    private ?string $id = null;
    private ?string $name = null;
    private ?StringList $favourites = null;

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

    public function getFavourites(): ?StringList
    {
        return $this->favourites;
    }

    public function setFavourites(?StringList $favourites): void
    {
        $this->favourites = $favourites;
    }
}

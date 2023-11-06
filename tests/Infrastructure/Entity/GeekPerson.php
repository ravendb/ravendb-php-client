<?php

namespace tests\RavenDB\Infrastructure\Entity;

class GeekPerson
{
    private ?string $name = null;
    private array $favoritePrimes = [];
    private array $favoriteVeryLargePrimes = [];

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getFavoritePrimes(): array
    {
        return $this->favoritePrimes;
    }

    public function setFavoritePrimes(array $favoritePrimes): void
    {
        $this->favoritePrimes = $favoritePrimes;
    }

    public function getFavoriteVeryLargePrimes(): array
    {
        return $this->favoriteVeryLargePrimes;
    }

    public function setFavoriteVeryLargePrimes(array $favoriteVeryLargePrimes): void
    {
        $this->favoriteVeryLargePrimes = $favoriteVeryLargePrimes;
    }
}

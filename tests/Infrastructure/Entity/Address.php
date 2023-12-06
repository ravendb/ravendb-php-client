<?php

namespace tests\RavenDB\Infrastructure\Entity;

class Address
{
    private ?string $id = null;
    private ?string $country = null;
    private ?string $city = null;
    private ?string $street = null;
    private ?int $zipCode = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): void
    {
        $this->country = $country;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): void
    {
        $this->city = $city;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): void
    {
        $this->street = $street;
    }

    public function getZipCode(): ?int
    {
        return $this->zipCode;
    }

    public function setZipCode(?int $zipCode): void
    {
        $this->zipCode = $zipCode;
    }
}

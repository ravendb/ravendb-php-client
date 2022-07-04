<?php

namespace tests\RavenDB\Infrastructure\Orders;

use DateTimeInterface;
use RavenDB\Type\StringList;

class Employee
{
    private ?string $id = null;
    private ?string $lastName = null;
    private ?string $firstName = null;
    private ?string $title = null;
    private ?Address $address = null;
    private ?DateTimeInterface $hiredAt = null;
    private ?DateTimeInterface $birthday = null;
    private ?string $homePhone = null;
    private ?string $extension = null;
    private ?string $reportsTo = null;
    private ?StringList $notes = null;
    private ?StringList $territories = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(?Address $address): void
    {
        $this->address = $address;
    }

    public function getHiredAt(): ?DateTimeInterface
    {
        return $this->hiredAt;
    }

    public function setHiredAt(?DateTimeInterface $hiredAt): void
    {
        $this->hiredAt = $hiredAt;
    }

    public function getBirthday(): ?DateTimeInterface
    {
        return $this->birthday;
    }

    public function setBirthday(?DateTimeInterface $birthday): void
    {
        $this->birthday = $birthday;
    }

    public function getHomePhone(): ?string
    {
        return $this->homePhone;
    }

    public function setHomePhone(?string $homePhone): void
    {
        $this->homePhone = $homePhone;
    }

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function setExtension(?string $extension): void
    {
        $this->extension = $extension;
    }

    public function getReportsTo(): ?string
    {
        return $this->reportsTo;
    }

    public function setReportsTo(?string $reportsTo): void
    {
        $this->reportsTo = $reportsTo;
    }

    public function getNotes(): ?StringList
    {
        return $this->notes;
    }

    /**
     * @param StringList|array|null $notes
     */
    public function setNotes($notes): void
    {
        if (is_array($notes)) {
            $notes = StringList::fromArray($notes);
        }
        $this->notes = $notes;
    }

    public function getTerritories(): ?StringList
    {
        return $this->territories;
    }

    public function setTerritories(?StringList $territories): void
    {
        $this->territories = $territories;
    }
}

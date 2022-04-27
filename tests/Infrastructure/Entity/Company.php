<?php

namespace tests\RavenDB\Infrastructure\Entity;

use RavenDB\Type\StringArray;

// !status: DONE
class Company
{
    private ?float $accountsReceivable = null;
    private ?string $id = null;
    private ?string $name = null;
    private ?string $desc = null;
    private ?string $email = null;
    private ?string $address1 = null;
    private ?string $address2 = null;
    private ?string $address3 = null;
    private ?ContactList $contacts = null;
    private ?int $phone = null;
    private ?CompanyType $type = null;
    private ?StringArray $employeesIds = null;

    public function getAccountsReceivable(): ?float
    {
        return $this->accountsReceivable;
    }

    public function setAccountsReceivable(?float $accountsReceivable): void
    {
        $this->accountsReceivable = $accountsReceivable;
    }

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

    public function getDesc(): ?string
    {
        return $this->desc;
    }

    public function setDesc(?string $desc): void
    {
        $this->desc = $desc;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getAddress1(): ?string
    {
        return $this->address1;
    }

    public function setAddress1(?string $address1): void
    {
        $this->address1 = $address1;
    }

    public function getAddress2(): ?string
    {
        return $this->address2;
    }

    public function setAddress2(?string $address2): void
    {
        $this->address2 = $address2;
    }

    public function getAddress3(): ?string
    {
        return $this->address3;
    }

    public function setAddress3(?string $address3): void
    {
        $this->address3 = $address3;
    }

    public function getContacts(): ?ContactList
    {
        return $this->contacts;
    }

    public function setContacts(?ContactList $contacts): void
    {
        $this->contacts = $contacts;
    }

    public function getPhone(): ?int
    {
        return $this->phone;
    }

    public function setPhone(?int $phone): void
    {
        $this->phone = $phone;
    }

    public function getType(): ?CompanyType
    {
        return $this->type;
    }

    public function setType(?CompanyType $type): void
    {
        $this->type = $type;
    }

    public function getEmployeesIds(): ?StringArray
    {
        return $this->employeesIds;
    }

    public function setEmployeesIds(?StringArray $employeesIds): void
    {
        $this->employeesIds = $employeesIds;
    }

}

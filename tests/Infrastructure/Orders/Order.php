<?php

namespace tests\RavenDB\Infrastructure\Orders;

use DateTimeInterface;

class Order
{
    private ?string $id = null;
    private ?string $company = null;
    private ?string $employee = null;
    private ?DateTimeInterface $orderedAt = null;
    private ?DateTimeInterface $requiredAt = null;
    private ?DateTimeInterface $shippedAt = null;
    private ?Address $shipTo = null;
    private ?string $shipVia = null;
    private ?float $freight = null;
    private ?OrderLineList $lines = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): void
    {
        $this->company = $company;
    }

    public function getEmployee(): ?string
    {
        return $this->employee;
    }

    public function setEmployee(?string $employee): void
    {
        $this->employee = $employee;
    }

    public function getOrderedAt(): ?DateTimeInterface
    {
        return $this->orderedAt;
    }

    public function setOrderedAt(?DateTimeInterface $orderedAt): void
    {
        $this->orderedAt = $orderedAt;
    }

    public function getRequiredAt(): ?DateTimeInterface
    {
        return $this->requiredAt;
    }

    public function setRequiredAt(?DateTimeInterface $requiredAt): void
    {
        $this->requiredAt = $requiredAt;
    }

    public function getShippedAt(): ?DateTimeInterface
    {
        return $this->shippedAt;
    }

    public function setShippedAt(?DateTimeInterface $shippedAt): void
    {
        $this->shippedAt = $shippedAt;
    }

    public function getShipTo(): ?Address
    {
        return $this->shipTo;
    }

    public function setShipTo(?Address $shipTo): void
    {
        $this->shipTo = $shipTo;
    }

    public function getShipVia(): ?string
    {
        return $this->shipVia;
    }

    public function setShipVia(?string $shipVia): void
    {
        $this->shipVia = $shipVia;
    }

    public function getFreight(): ?float
    {
        return $this->freight;
    }

    public function setFreight(?float $freight): void
    {
        $this->freight = $freight;
    }

    public function getLines(): ?OrderLineList
    {
        return $this->lines;
    }

    public function setLines(?OrderLineList $lines): void
    {
        $this->lines = $lines;
    }
}

<?php

namespace tests\RavenDB\Test\MailingList\_NoTrackingTest;

class AA
{
    private ?string $id = null;
    private ?array $bs = [];

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function & getBs(): ?array
    {
        return $this->bs;
    }

    public function setBs(?array $bs): void
    {
        $this->bs = $bs;
    }
}

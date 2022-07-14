<?php

namespace tests\RavenDB\Test\Issues\RavenDB_15143Test;

class Locker
{
    private ?string $clientId = null;

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function setClientId(?string $clientId): void
    {
        $this->clientId = $clientId;
    }
}

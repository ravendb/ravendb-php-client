<?php

namespace RavenDB\Documents\Operations\Etl\ElasticSearch\Authentication;

class ElasticSearchBasicAuthentication
{
    private ?string $username = null;
    private ?string $password = null;

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }
}

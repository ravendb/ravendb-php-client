<?php

namespace RavenDB\ServerWide\Operations\Certificates;

class EditClientCertificateParameters
{
    private ?string $thumbprint = null;
    private ?DatabaseAccessArray $permissions = null;
    private ?string $name = null;
    private ?SecurityClearance $clearance = null;

    public function getThumbprint(): ?string
    {
        return $this->thumbprint;
    }

    public function setThumbprint(?string $thumbprint): void
    {
        $this->thumbprint = $thumbprint;
    }

    public function getPermissions(): ?DatabaseAccessArray
    {
        return $this->permissions;
    }

    public function setPermissions(?DatabaseAccessArray $permissions): void
    {
        $this->permissions = $permissions;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getClearance(): ?SecurityClearance
    {
        return $this->clearance;
    }

    public function setClearance(?SecurityClearance $clearance): void
    {
        $this->clearance = $clearance;
    }
}

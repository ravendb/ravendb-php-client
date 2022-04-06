<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\RavenCommand;
use RavenDB\ServerWide\Operations\ServerOperationInterface;

class CreateClientCertificateOperation implements ServerOperationInterface
{
    private string $name;
    private DatabaseAccessArray $permissions;
    private ?SecurityClearance $clearance;
    private ?string $password;

    public function __construct(?string $name, ?DatabaseAccessArray $permissions, ?SecurityClearance $clearance, ?string $password = null)
    {
        if ($name == null) {
            throw new IllegalArgumentException("Name cannot be null");
        }

        if ($permissions == null) {
            throw new IllegalArgumentException("Permission cannot be null");
        }

        $this->name = $name;
        $this->permissions = $permissions;
        $this->clearance = $clearance;
        $this->password = $password;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new CreateClientCertificateCommand($this->name, $this->permissions, $this->clearance, $this->password);
    }
}

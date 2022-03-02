<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\ServerWide\Operations\VoidServerOperationInterface;

class PutClientCertificateOperation implements VoidServerOperationInterface
{
    private string $certificate;
    private DatabaseAccessArray $permissions;
    private string $name;
    private ?SecurityClearance $clearance = null;

    public function __construct(
        ?string $name,
        ?string $certificate,
        ?DatabaseAccessArray $permissions,
        ?SecurityClearance $clearance = null
    ) {
        if ($certificate == null) {
            throw new IllegalArgumentException('Certificate cannot be null');
        }

        if ($permissions == null) {
            throw new IllegalArgumentException('Permissions cannot be null');
        }

        if ($name == null) {
            throw new IllegalArgumentException('Name cannot be null');
        }

        $this->certificate = $certificate;
        $this->permissions = $permissions;
        $this->name = $name;
        $this->clearance = $clearance;
    }

    public function getCommand(DocumentConventions $conventions): VoidRavenCommand
    {
        return new PutClientCertificateCommand($this->name, $this->certificate, $this->permissions, $this->clearance);
    }
}

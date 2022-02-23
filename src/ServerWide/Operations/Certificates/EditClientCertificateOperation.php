<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\ServerWide\Operations\VoidServerOperationInterface;

class EditClientCertificateOperation implements VoidServerOperationInterface
{
    private string $thumbprint;
    private DatabaseAccessArray $permissions;
    private string $name;
    private SecurityClearance $clearance;

    public function __construct(?EditClientCertificateParameters $parameters)
    {
        if ($parameters == null) {
            throw new IllegalArgumentException("Parameters cannot be null");
        }
        if ($parameters->getName() == null) {
            throw new IllegalArgumentException("Name cannot be null");
        }
        if ($parameters->getThumbprint() == null) {
            throw new IllegalArgumentException("Thumbprint cannot be null");
        }
        if ($parameters->getPermissions() == null) {
            throw new IllegalArgumentException("Permissions cannot be null");
        }

        $this->name = $parameters->getName();
        $this->thumbprint = $parameters->getThumbprint();
        $this->permissions = $parameters->getPermissions();
        $this->clearance = $parameters->getClearance();
    }

    public function getCommand(DocumentConventions $conventions): VoidRavenCommand
    {
        return new EditClientCertificateCommand(
                $this->thumbprint,
                $this->name,
                $this->permissions,
                $this->clearance
            );
    }
}

<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\ServerWide\Operations\VoidServerOperationInterface;

class DeleteCertificateOperation implements VoidServerOperationInterface
{
    private string $thumbprint;

    public function __construct(?string $thumbprint)
    {
        if ($thumbprint == null) {
            throw new IllegalArgumentException("Thumbprint cannot be null");
        }

        $this->thumbprint = $thumbprint;
    }

    public function getCommand(DocumentConventions $conventions): VoidRavenCommand
    {
        return new DeleteCertificateCommand($this->thumbprint);
    }
}

<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\RavenCommand;
use RavenDB\ServerWide\Operations\ServerOperationInterface;

class GetCertificateMetadataOperation implements ServerOperationInterface
{
    private string $thumbprint;

    public function __construct(?string $thumbprint)
    {
        if ($thumbprint == null) {
            throw new IllegalArgumentException("Thumbprint cannot be null.");
        }

        $this->thumbprint = $thumbprint;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new GetCertificateMetadataCommand($this->thumbprint);
    }
}

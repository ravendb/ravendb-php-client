<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Http\RavenCommand;
use RavenDB\ServerWide\Operations\ServerOperationInterface;

class GetCertificatesMetadataOperation implements ServerOperationInterface
{
    private ?string $name = null;

    public function __construct(?string $name = null)
    {
        $this->name = $name;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new GetCertificatesMetadataCommand($this->name);
    }
}

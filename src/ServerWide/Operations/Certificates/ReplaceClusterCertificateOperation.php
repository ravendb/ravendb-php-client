<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\ServerWide\Operations\VoidServerOperationInterface;

class ReplaceClusterCertificateOperation implements VoidServerOperationInterface
{
    private $certBytes;
    private bool $replaceImmediately;

    public function __construct($certBytes, bool $replaceImmediately)
    {
        if ($certBytes == null) {
            throw new IllegalArgumentException("CertBytes cannot be null");
        }

        $this->certBytes = $certBytes;
        $this->replaceImmediately = $replaceImmediately;
    }

    public function getCommand(DocumentConventions $conventions): VoidRavenCommand
    {
        return new ReplaceClusterCertificateCommand($this->certBytes, $this->replaceImmediately);
    }
}

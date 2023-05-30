<?php

namespace RavenDB\Documents\Operations\Etl;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\VoidMaintenanceOperationInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\VoidRavenCommand;

class ResetEtlOperation implements VoidMaintenanceOperationInterface
{
    private ?string $configurationName = null;
    private ?string $transformationName = null;

    public function __construct(?string $configurationName, ?string $transformationName) {
        if ($configurationName == null) {
            throw new IllegalArgumentException("ConfigurationName cannot be null");
        }

        if ($transformationName == null) {
            throw new IllegalArgumentException("TransformationName cannot be null");
        }

        $this->configurationName = $configurationName;
        $this->transformationName = $transformationName;
    }

    public function getCommand(DocumentConventions $conventions): VoidRavenCommand
    {
        return new ResetEtlCommand($this->configurationName, $this->transformationName);
    }
}

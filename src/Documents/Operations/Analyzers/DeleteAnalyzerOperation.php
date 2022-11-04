<?php

namespace RavenDB\Documents\Operations\Analyzers;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\VoidMaintenanceOperationInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\VoidRavenCommand;

class DeleteAnalyzerOperation implements VoidMaintenanceOperationInterface
{
    private ?string $analyzerName = null;

    public function __construct(?string $analyzerName)
    {
        if ($analyzerName == null) {
            throw new IllegalArgumentException("AnalyzerName cannot be null");
        }

        $this->analyzerName = $analyzerName;
    }

    function getCommand(DocumentConventions $conventions): VoidRavenCommand
    {
        return new DeleteAnalyzerCommand($this->analyzerName);
    }
}

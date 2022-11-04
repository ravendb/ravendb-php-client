<?php

namespace RavenDB\Documents\Operations\Analyzers;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Indexes\Analysis\AnalyzerDefinition;
use RavenDB\Documents\Indexes\Analysis\AnalyzerDefinitionArray;
use RavenDB\Documents\Operations\VoidMaintenanceOperationInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\VoidRavenCommand;

class PutAnalyzersOperation implements VoidMaintenanceOperationInterface
{
    private ?AnalyzerDefinitionArray $analyzersToAdd = null;

    public function __construct(AnalyzerDefinition ...$analyzersToAdd)
    {
        if (empty($analyzersToAdd)) {
            throw new IllegalArgumentException("AnalyzersToAdd cannot be null or empty");
        }

        $this->analyzersToAdd = AnalyzerDefinitionArray::fromArray($analyzersToAdd);
    }

    function getCommand(DocumentConventions $conventions): VoidRavenCommand
    {
        return new PutAnalyzersCommand($conventions, $this->analyzersToAdd);
    }
}

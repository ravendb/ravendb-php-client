<?php

namespace RavenDB\ServerWide\Operations\Analyzers;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Indexes\Analysis\AnalyzerDefinition;
use RavenDB\Documents\Indexes\Analysis\AnalyzerDefinitionArray;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\RavenCommand;
use RavenDB\ServerWide\Operations\ServerOperationInterface;

class PutServerWideAnalyzersOperation implements ServerOperationInterface
{
    private ?AnalyzerDefinitionArray $analyzersToAdd = null;

    public function __construct(AnalyzerDefinition ...$analyzersToAdd)
    {
        if (empty($analyzersToAdd)) {
            throw new IllegalArgumentException("AnalyzersToAdd cannot be null or empty");
        }

        $this->analyzersToAdd = AnalyzerDefinitionArray::fromArray($analyzersToAdd);
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new PutServerWideAnalyzersCommand($conventions, $this->analyzersToAdd);
    }
}

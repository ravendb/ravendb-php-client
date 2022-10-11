<?php

namespace RavenDB\ServerWide\Operations\Analyzers;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\ServerWide\Operations\VoidServerOperationInterface;

class DeleteServerWideAnalyzerOperation implements VoidServerOperationInterface
{
    private ?string $analyzerName = null;

    public function __construct(?string $analyzerName)
    {
        if ($analyzerName == null) {
            throw new IllegalArgumentException("AnalyzerName cannot be null");
        }

        $this->analyzerName = $analyzerName;
    }


    public function getCommand(DocumentConventions $conventions): VoidRavenCommand
    {
        return new DeleteServerWideAnalyzerCommand($this->analyzerName);
    }
}

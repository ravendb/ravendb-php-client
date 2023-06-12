<?php

namespace RavenDB\ServerWide\Operations\DocumentsCompression;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\MaintenanceOperationInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\RavenCommand;
use RavenDB\ServerWide\DocumentsCompressionConfiguration;

class UpdateDocumentsCompressionConfigurationOperation implements MaintenanceOperationInterface
{
    private ?DocumentsCompressionConfiguration $documentsCompressionConfiguration = null;

    public function __construct(?DocumentsCompressionConfiguration $configuration)
    {
        if ($configuration == null) {
            throw new IllegalArgumentException("Configuration cannot be null");
        }
        $this->documentsCompressionConfiguration = $configuration;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new UpdateDocumentCompressionConfigurationCommand($this->documentsCompressionConfiguration);
    }
}

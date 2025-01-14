<?php

namespace RavenDB\ServerWide\Operations;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\Revisions\RevisionsCollectionConfiguration;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\RavenCommand;

class ConfigureRevisionsForConflictsOperation implements ServerOperationInterface
{
    private ?string $database = null;
    private ?RevisionsCollectionConfiguration $configuration = null;

    public function __construct(?string $database, ?RevisionsCollectionConfiguration $configuration)
    {

        $this->database = $database;

        if ($configuration == null) {
            throw new IllegalArgumentException("Configuration cannot be null");
        }
        $this->configuration = $configuration;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new ConfigureRevisionsForConflictsCommand($conventions, $this->database, $this->configuration);
    }
}

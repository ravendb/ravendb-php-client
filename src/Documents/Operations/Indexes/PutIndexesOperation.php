<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Indexes\IndexDefinition;
use RavenDB\Documents\Indexes\IndexDefinitionArray;
use RavenDB\Documents\Operations\MaintenanceOperationInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\RavenCommand;

// !status: DONE
class PutIndexesOperation implements MaintenanceOperationInterface
{
    private ?IndexDefinitionArray $indexToAdd = null;
    private bool $allJavaScriptIndexes = false;

    public function __construct(IndexDefinition ...$indexToAdd) {
        if (empty($indexToAdd)) {
            throw new IllegalArgumentException("indexToAdd cannot be null");
        }

        $this->indexToAdd = IndexDefinitionArray::fromArray($indexToAdd);
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new PutIndexesCommand($conventions, $this->indexToAdd);
    }
}

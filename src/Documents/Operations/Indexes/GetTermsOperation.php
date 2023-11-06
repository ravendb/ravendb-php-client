<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\MaintenanceOperationInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\RavenCommand;

class GetTermsOperation implements MaintenanceOperationInterface
{
    private ?string $indexName;
    private ?string $field;
    private ?string $fromValue;
    private ?int $pageSize = null;

    public function __construct(?string $indexName, ?string $field, ?string $fromValue, ?int $pageSize = null)
    {
        if ($indexName == null) {
            throw new IllegalArgumentException("IndexName cannot be null");
        }

        if ($field == null) {
            throw new IllegalArgumentException("Field cannot be null");
        }

        $this->indexName = $indexName;
        $this->field = $field;
        $this->fromValue = $fromValue;
        $this->pageSize = $pageSize;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new GetTermsCommand($this->indexName, $this->field, $this->fromValue, $this->pageSize);
    }
}

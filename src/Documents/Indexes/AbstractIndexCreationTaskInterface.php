<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\DocumentStoreInterface;

interface AbstractIndexCreationTaskInterface
{
    public function getIndexName(): ?string;
    public function getPriority(): ?IndexPriority;
    public function getState(): ?IndexState;
    public function getDeploymentMode(): ?IndexDeploymentMode;
    public function getConventions(): ?DocumentConventions;
    public function setConventions(?DocumentConventions $conventions): void;
    public function createIndexDefinition(): IndexDefinition;
    public function execute(?DocumentStoreInterface $store, ?DocumentConventions $conventions, ?string $database = null): void;
}

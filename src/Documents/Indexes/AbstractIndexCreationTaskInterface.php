<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\DocumentStoreInterface;

interface AbstractIndexCreationTaskInterface
{
    function getIndexName(): ?string;
    function getPriority(): ?IndexPriority;
    function getState(): ?IndexState;
    function getDeploymentMode(): ?IndexDeploymentMode;
    function getConventions(): ?DocumentConventions;
    function setConventions(?DocumentConventions $conventions): void;
    function createIndexDefinition(): IndexDefinition;
    function execute(?DocumentStoreInterface $store, ?DocumentConventions $conventions, ?string $database = null): void;
}

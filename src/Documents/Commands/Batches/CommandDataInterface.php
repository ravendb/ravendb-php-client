<?php

namespace RavenDB\Documents\Commands\Batches;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;

interface CommandDataInterface
{
    public function getId(): ?string;
    public function getName(): ?string;
    public function getChangeVector(): ?string;
    public function getType(): ?CommandType;
    public function serialize(?DocumentConventions $conventions): array;
    public function onBeforeSaveChanges(?InMemoryDocumentSessionOperations $session): void;
}

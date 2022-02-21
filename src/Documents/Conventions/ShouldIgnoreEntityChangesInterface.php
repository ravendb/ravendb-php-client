<?php

namespace RavenDB\Documents\Conventions;

use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;

interface ShouldIgnoreEntityChangesInterface
{
    public function check(InMemoryDocumentSessionOperations  $sessionOperations, object $entity, string $documentId): bool;
}

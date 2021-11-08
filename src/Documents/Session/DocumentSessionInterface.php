<?php

namespace RavenDB\Documents\Session;

interface DocumentSessionInterface
{

    public function store(object $entity, ?string $id = null): void;
//   public function _store(object $entity, string $changeVector, string $id);

    public function saveChanges(): void;
}

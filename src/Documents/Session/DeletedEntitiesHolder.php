<?php

namespace RavenDB\Documents\Session;

use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Primitives\CleanCloseable;
use RavenDB\Type\TypedArray;

//@todo: implement this
class DeletedEntitiesHolder implements CleanCloseable
{
    private array $deletedEntities = [];

    private ?array $onBeforeDeletedEntities = [];

    private bool $prepareEntitiesDeletes = false;

    public function isEmpty(): bool
    {
        return $this->size() == 0;
    }

    public function size(): int
    {
        return count($this->deletedEntities) + count($this->onBeforeDeletedEntities);
    }

    public function add(object $entity): void
    {
        if ($this->prepareEntitiesDeletes) {
            $this->onBeforeDeletedEntities[] = $entity;
            return;
        }

        $this->deletedEntities[] = $entity;
    }

    public function remove(object $entity): void
    {
        if ($key = array_search($entity, $this->deletedEntities) !== false) {
            unset($this->deletedEntities[$key]);
        }

        if ($key = array_search($entity, $this->onBeforeDeletedEntities) !== false) {
            unset($this->onBeforeDeletedEntities[$key]);
        }
    }

    public function evict(object $entity)
    {
        if ($this->prepareEntitiesDeletes) {
            throw new IllegalStateException('Cannot Evict entity during OnBeforeDelete');
        }

        if ($key = array_search($entity, $this->deletedEntities) !== false) {
            unset($this->deletedEntities[$key]);
        }
    }

    public function contains(object $entity): bool
    {
        if (in_array($entity, $this->deletedEntities)) {
            return true;
        }

        return in_array($entity, $this->onBeforeDeletedEntities);
    }

    public function clear(): void
    {
        $this->deletedEntities = [];
        $this->onBeforeDeletedEntities = [];
    }

    public function close(): void
    {
        $this->prepareEntitiesDeletes = false;
    }

    public function prepareEntitiesDeletes(): CleanCloseable
    {
        $this->prepareEntitiesDeletes = true;

        return $this;
    }

    public function getDeletedEntitiesEnumeratorResults(): \Generator
    {
        foreach ($this->deletedEntities as $entity) {
            yield new DeletedEntitiesEnumeratorResult($entity, true);
        }

        foreach ($this->onBeforeDeletedEntities as $entity) {
            yield new DeletedEntitiesEnumeratorResult($entity, false);
        }
    }
}

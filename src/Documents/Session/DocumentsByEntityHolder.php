<?php

namespace RavenDB\Documents\Session;

use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Primitives\CleanCloseable;

use DS\Map as DSMap;

class DocumentsByEntityHolder implements CleanCloseable
{
    private ?DSMap $documentsByEntity = null;

    private ?DSMap $onBeforeStoreDocumentsByEntity = null;

    private bool $prepareEntitiesPuts = false;

    public function __construct()
    {
        $this->documentsByEntity = new DSMap();
    }

    public function size(): int
    {
        $size = $this->documentsByEntity->count();

        if ($this->onBeforeStoreDocumentsByEntity != null) {
            $size += $this->onBeforeStoreDocumentsByEntity->count();
        }

        return $size;
    }

    public function remove(object $entity): void
    {
        $this->documentsByEntity->remove($entity);

        if ($this->onBeforeStoreDocumentsByEntity != null) {
            $this->onBeforeStoreDocumentsByEntity->remove($entity);
        }
    }

    public function evict(object $entity): void
    {
        if ($this->prepareEntitiesPuts) {
            throw new IllegalStateException('Cannot Evict entity during OnBeforeStore');
        }

        $this->documentsByEntity->remove($entity);
    }

    public function put(object $entity, DocumentInfo $documentInfo): void
    {
        if (!$this->prepareEntitiesPuts) {
            $this->documentsByEntity->put($entity, $documentInfo);
            return;
        }

        $this->createOnBeforeStoreDocumentsByEntityIfNeeded();
        $this->onBeforeStoreDocumentsByEntity->put($entity, $documentInfo);
    }

    private function createOnBeforeStoreDocumentsByEntityIfNeeded()
    {
        if ($this->onBeforeStoreDocumentsByEntity != null) {
            return;
        }

        $this->onBeforeStoreDocumentsByEntity = new DSMap();
    }

    public function clear(): void
    {
        $this->documentsByEntity->clear();
        if ($this->onBeforeStoreDocumentsByEntity != null) {
            $this->onBeforeStoreDocumentsByEntity->clear();
        }
    }

    public function get(object $entity): ?DocumentInfo
    {
        if ($this->documentsByEntity->hasKey($entity)) {
            return $this->documentsByEntity->get($entity);
        }

        if ($this->onBeforeStoreDocumentsByEntity == null) {
            return null;
        }

        if ($this->onBeforeStoreDocumentsByEntity->hasKey($entity)) {
            return $this->onBeforeStoreDocumentsByEntity->get($entity);
        }

        return null;
    }

    public function close(): void
    {
        $this->prepareEntitiesPuts = false;
    }

    public function prepareEntitiesPuts(): CleanCloseable
    {
        $this->prepareEntitiesPuts = true;

        return $this;
    }

    public function getDocumentsByEntityEnumeratorResults(): \Generator
    {
        foreach ($this->documentsByEntity->keys() as $entity) {
            yield new DocumentsByEntityEnumeratorResult(
                $entity,
                $this->documentsByEntity->get($entity),
                true
            );
        }

        if ($this->onBeforeStoreDocumentsByEntity != null) {
            foreach ($this->onBeforeStoreDocumentsByEntity->keys() as $entity) {
                yield new DocumentsByEntityEnumeratorResult(
                    $entity,
                    $this->onBeforeStoreDocumentsByEntity->get($entity),
                    false
                );
            }
        }
    }
}

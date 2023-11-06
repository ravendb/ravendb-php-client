<?php

namespace RavenDB\Documents\Identity;

use RavenDB\Documents\DocumentStore;
use RavenDB\Type\ExtendedArrayObject;

class MultiDatabaseHiLoIdGenerator implements HiLoIdGeneratorInterface
{
    protected ?DocumentStore $store = null;

    private ?ExtendedArrayObject $generators = null;

    public function __construct(?DocumentStore $store) {
        $this->generators = new ExtendedArrayObject();
        $this->store = $store;
    }

    public function generateDocumentId(?string $database, object $entity): string
    {
        $database = $this->store->getEffectiveDatabase($database);
        if (!$this->generators->offsetExists($database)) {
            $this->generators->offsetSet($database, $this->generateMultiTypeHiLoFunc($database));
        }
        $generator = $this->generators->offsetGet($database);
        return $generator->generateDocumentId($entity);
    }

    public function generateMultiTypeHiLoFunc(?string $database): MultiTypeHiLoIdGenerator
    {
        return new MultiTypeHiLoIdGenerator($this->store, $database);
    }

    public function returnUnusedRange(): void
    {
        foreach ($this->generators as $generator) {
            $generator->returnUnusedRange();
        }
    }

    public function generateNextIdFor(?string $database, null|string|object $collectionNameOrEntity): int
    {
        $collectionName = $this->store->getConventions()->getCollectionName($collectionNameOrEntity);;

        $database = $this->store->getEffectiveDatabase($database);
        if (!$this->generators->offsetExists($database)) {
            $this->generators->offsetSet($database, $this->generateMultiTypeHiLoFunc($database));
        }

        /** @var MultiTypeHiLoIdGenerator $generator */
        $generator = $this->generators->offsetGet($database);

        return $generator->generateNextIdFor($collectionName);
    }
}

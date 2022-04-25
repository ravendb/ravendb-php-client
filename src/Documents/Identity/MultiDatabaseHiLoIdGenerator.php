<?php

namespace RavenDB\Documents\Identity;

use RavenDB\Documents\DocumentStore;
use RavenDB\Type\ExtendedArrayObject;

// !status: DONE
class MultiDatabaseHiLoIdGenerator
{
    protected ?DocumentStore $store = null;

    private ?ExtendedArrayObject $generators = null;

    public function __construct(?DocumentStore $store) {
        $this->generators = new ExtendedArrayObject();
        $this->store = $store;
    }

    public function generateDocumentId(string $database, object $entity): string
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
}

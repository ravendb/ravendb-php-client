<?php

namespace RavenDB\Documents\Session;

use RavenDB\Type\TypedArray;

//@todo: implement this
class DocumentsByEntityHolder extends TypedArray
{
    public function __construct()
    {
        parent::__construct(DocumentsByEntityEnumeratorResult::class);
    }

    public function get(object $entity): ?DocumentInfo
    {
        //@todo: implement this
        return null;
    }

    public function put(?object $entity, DocumentInfo $documentInfo): void
    {
        //@todo: implement this
    }

    public function remove(object $entity): void
    {
        //@todo: implement this
    }

    public function clear(): void
    {
        //@todo: implement this
    }
}

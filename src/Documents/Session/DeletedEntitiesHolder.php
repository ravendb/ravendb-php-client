<?php

namespace RavenDB\Documents\Session;

use RavenDB\Type\TypedArray;

//@todo: implement this
class DeletedEntitiesHolder extends TypedArray
{
    public function __construct()
    {
        parent::__construct(DeletedEntitiesEnumeratorResult::class);
    }

    public function add(?object $entity): void
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

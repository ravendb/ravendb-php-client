<?php

namespace RavenDB\Documents;

use RavenDB\Type\TypedArray;

class DocumentStoreArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(DocumentStore::class);
    }
}

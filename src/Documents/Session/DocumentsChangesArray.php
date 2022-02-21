<?php

namespace RavenDB\Documents\Session;

use RavenDB\Type\TypedArray;

class DocumentsChangesArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(DocumentsChanges::class);
    }
}

<?php

namespace RavenDB\Documents\Session;

use RavenDB\Type\TypedMap;

class DocumentsById extends TypedMap
{
    public function __construct()
    {
        parent::__construct(DocumentInfo::class);
    }

    public function add(DocumentInfo $documentInfo)
    {
        $this[$documentInfo->getId()] = $documentInfo;
    }

    public function getValue($id): DocumentInfo
    {
        return $this[$id];
    }

    public function clear(): void
    {
        $this->exchangeArray([]);
    }
}

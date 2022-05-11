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
        if ($this->offsetExists($documentInfo->getId())) {
            return;
        }
        $this[$documentInfo->getId()] = $documentInfo;
    }

    public function &getValue($id): ?DocumentInfo
    {
        $result = null;
        if ($this->offsetExists($id)) {
            $result = $this->offsetGet($id);
        }

        return $result;
    }

    public function remove(string $id): void
    {
        if ($this->offsetExists($id)) {
            parent::offsetUnset($id);
        }
    }

    public function clear(): void
    {
        $this->exchangeArray([]);
    }
}

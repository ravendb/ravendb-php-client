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

    public function getValue($id): ?DocumentInfo
    {
        if (!$this->offsetExists($id)) {
            return null;
        }

        return $this->offsetGet($id);
    }

    public function remove(string $id): void
    {
        if ($this->offsetExists($id)) {
            $this->remove($id);
        }
    }

    public function clear(): void
    {
        $this->exchangeArray([]);
    }
}

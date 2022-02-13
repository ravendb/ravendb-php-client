<?php

namespace RavenDB\Documents\Session;

use RavenDB\Type\TypedArray;

class DocumentInfoArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(DocumentInfo::class);
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
            $this->offsetUnset($id);
        }
    }
}

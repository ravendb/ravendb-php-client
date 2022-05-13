<?php

namespace RavenDB\Documents\Session;

use RavenDB\Type\TypedArray;

class DocumentInfoArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(DocumentInfo::class);
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
            $this->offsetUnset($id);
        }
    }
}

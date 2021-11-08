<?php

namespace RavenDB\Json;

use RavenDB\Documents\Session\MetadataDictionaryInterface;

class MetadataAsDictionary implements MetadataDictionaryInterface
{

    public function isDirty(): bool
    {
        // TODO: Implement isDirty() method.
        return false;
    }
}

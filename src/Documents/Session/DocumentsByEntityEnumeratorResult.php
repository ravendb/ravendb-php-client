<?php

namespace RavenDB\Documents\Session;

class DocumentsByEntityEnumeratorResult
{
    private object $key;
    private DocumentInfo $value;
    private bool $executeOnBeforeStore;

    public function __construct(object $key, DocumentInfo $value, bool $executeOnBeforeStore)
    {
        $this->key = $key;
        $this->value = $value;
        $this->executeOnBeforeStore = $executeOnBeforeStore;
    }

    public function getKey(): object
    {
        return $this->key;
    }

    public function getValue(): DocumentInfo
    {
        return $this->value;
    }

    public function isExecuteOnBeforeStore(): bool
    {
        return $this->executeOnBeforeStore;
    }
}

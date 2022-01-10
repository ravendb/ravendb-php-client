<?php

namespace RavenDB\Documents\Session;

class DeletedEntitiesEnumeratorResult
{
    private object $entity;
    private bool $executeOnBeforeDelete;

    public function __construct(object $entity, bool $executeOnBeforeDelete)
    {
        $this->entity = $entity;
        $this->executeOnBeforeDelete = $executeOnBeforeDelete;
    }

    public function getEntity(): object
    {
        return $this->entity;
    }

    public function isExecuteOnBeforeDelete(): bool
    {
        return $this->executeOnBeforeDelete;
    }
}

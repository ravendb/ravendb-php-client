<?php

namespace RavenDB\Documents\Session;

interface AdvancedDocumentSessionOperationsInterface
{
    /**
     * Returns all changes for each entity stored within session.
     * Including name of the field/property that changed, its old and new value and change type.
     *
     * @return array Document changes
     */
    public function whatChanged(): array;
}

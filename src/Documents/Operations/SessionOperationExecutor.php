<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;

class SessionOperationExecutor extends OperationExecutor
{
    private ?InMemoryDocumentSessionOperations $session = null;

    public function __construct(?InMemoryDocumentSessionOperations $session)
    {
        parent::__construct($session->getDocumentStore(), $session->getDatabaseName());

        $this->session = $session;
    }

    public function forDatabase(?string $databaseName): OperationExecutor
    {
        throw new IllegalStateException("The method is not supported");
    }
}

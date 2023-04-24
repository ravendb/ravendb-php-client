<?php

namespace RavenDB\Documents\Session\Operations;

use RavenDB\Documents\Commands\MultiGet\GetRequestList;
use RavenDB\Documents\Commands\MultiGet\MultiGetCommand;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;

class MultiGetOperation
{
    private ?InMemoryDocumentSessionOperations $session = null;

    public function __construct(?InMemoryDocumentSessionOperations $session)
    {
        $this->session = $session;
    }

    public function createRequest(GetRequestList $requests): MultiGetCommand
    {
        return new MultiGetCommand($this->session->getRequestExecutor(), $requests);
    }

    public function setResult(array $result): void
    {

    }
}

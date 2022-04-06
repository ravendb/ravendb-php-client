<?php

namespace RavenDB\Documents\Session;

use RavenDB\Primitives\EventArgs;

class SessionClosingEventArgs extends EventArgs
{
    private InMemoryDocumentSessionOperations $session;

    public function __construct(InMemoryDocumentSessionOperations $session)
    {
        $this->session = $session;
    }

    public function getSession(): InMemoryDocumentSessionOperations
    {
        return $this->session;
    }
}

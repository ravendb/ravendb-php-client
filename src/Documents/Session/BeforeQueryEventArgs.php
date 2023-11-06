<?php

namespace RavenDB\Documents\Session;

use RavenDB\Primitives\EventArgs;

class BeforeQueryEventArgs extends EventArgs
{
    private InMemoryDocumentSessionOperations $session;
    private DocumentQueryCustomizationInterface $queryCustomization;

    public function __construct(InMemoryDocumentSessionOperations $session, DocumentQueryCustomizationInterface $queryCustomization) {
        $this->session = $session;
        $this->queryCustomization = $queryCustomization;
    }

    public function getSession(): InMemoryDocumentSessionOperations
    {
        return $this->session;
    }

    public function getQueryCustomization(): DocumentQueryCustomizationInterface
    {
        return $this->queryCustomization;
    }
}

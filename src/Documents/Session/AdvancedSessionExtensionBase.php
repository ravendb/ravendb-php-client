<?php

namespace RavenDB\Documents\Session;

use RavenDB\Documents\Commands\Batches\CommandDataInterface;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Http\RequestExecutor;
use RavenDB\Type\DeferredCommandsMap;

abstract class AdvancedSessionExtensionBase
{
    protected ?InMemoryDocumentSessionOperations $session = null;
    protected ?RequestExecutor $requestExecutor = null;
    protected ?SessionInfo $sessionInfo = null;
    protected ?DocumentStoreInterface $documentStore = null;
    protected ?DeferredCommandsMap $deferredCommandsMap = null;
    protected ?DocumentsById $documentsById = null;

    protected function __construct(?InMemoryDocumentSessionOperations $session)
    {
        $this->session = $session;
        $this->requestExecutor = $session->getRequestExecutor();
        $this->sessionInfo = $session->getSessionInfo();
        $this->documentStore = $session->getDocumentStore();
        $this->deferredCommandsMap = $session->deferredCommandsMap;
        $this->documentsById = $session->documentsById;
    }

    public function defer(?CommandDataInterface $command, ...$commands): void
    {
        $this->session->defer($command, $commands);
    }
}

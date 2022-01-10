<?php

namespace RavenDB\Documents\Session;

use RavenDB\Documents\Commands\Batches\BatchOptions;
use RavenDB\Documents\Commands\Batches\CommandDataInterface;

class SaveChangesData
{
    /** @var array<CommandDataInterface> $deferredCommands */
    private array $deferredCommands = [];
    private array $deferredCommandsMap = [];
    private array $sessionCommands = [];
    private array $entities = [];
    private ?BatchOptions $options;
    private ActionsToRunOnSuccess $onSuccess;

    public function __construct(InMemoryDocumentSessionOperations $session)
    {
        $this->deferredCommands = $session->deferredCommands;
        $this->deferredCommandsMap = $session->deferredCommandsMap;
        $this->options = $session->saveChangesOptions;
        $this->onSuccess = new ActionsToRunOnSuccess($session);
    }

    public function getOnSuccess(): ActionsToRunOnSuccess
    {
        return $this->onSuccess;
    }

    public function getDeferredCommands(): array
    {
        return $this->deferredCommands;
    }

    public function getDeferredCommandsMap(): array
    {
        return $this->deferredCommandsMap;
    }

    public function getSessionCommands(): array
    {
        return $this->sessionCommands;
    }

    public function getEntities(): array
    {
        return $this->entities;
    }

    public function getOptions(): ?BatchOptions
    {
        return $this->options;
    }
}

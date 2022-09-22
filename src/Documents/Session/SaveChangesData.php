<?php

namespace RavenDB\Documents\Session;

use RavenDB\Documents\Commands\Batches\BatchOptions;
use RavenDB\Documents\Commands\Batches\CommandDataInterface;

use Ds\Map as DSMap;
use RavenDB\Type\DeferredCommandsMap;

class SaveChangesData
{
    /** @var array<CommandDataInterface> $deferredCommands */
    private array $deferredCommands = [];
    private DeferredCommandsMap $deferredCommandsMap;
    private array $sessionCommands = [];
    private array $entities = [];
    private ?BatchOptions $options;
    private ActionsToRunOnSuccess $onSuccess;

    public function __construct(InMemoryDocumentSessionOperations $session)
    {
        $this->deferredCommands = $session->deferredCommands;
        $this->deferredCommandsMap = new DeferredCommandsMap($session->deferredCommandsMap);

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

    public function addDeferredCommand($command): void
    {
        $this->deferredCommands[] = $command;
    }

    public function & getDeferredCommandsMap(): DeferredCommandsMap
    {
        return $this->deferredCommandsMap;
    }

    public function getSessionCommands(): array
    {
        return $this->sessionCommands;
    }

    public function addSessionCommand($command): void
    {
        $this->sessionCommands[] = $command;
    }

    public function getEntities(): array
    {
        return $this->entities;
    }

    public function addEntity($entity): void
    {
        $this->entities[] = $entity;
    }

    public function getOptions(): ?BatchOptions
    {
        return $this->options;
    }

    public function addOption($option): void
    {
        $this->options[] = $option;
    }
}

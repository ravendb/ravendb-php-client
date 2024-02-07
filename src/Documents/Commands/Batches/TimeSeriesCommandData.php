<?php

namespace RavenDB\Documents\Commands\Batches;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesOperation;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Exceptions\IllegalArgumentException;

abstract class TimeSeriesCommandData implements CommandDataInterface
{
    private ?string $id = null;
    private ?string $name = null;
    private ?TimeSeriesOperation $timeSeries = null;

    private ?bool $fromEtl = null;

    public function __construct(?string $documentId, ?string $name)
    {
        if ($documentId == null) {
            throw new IllegalArgumentException("DocumentId cannot be null");
        }

        if ($name == null) {
            throw new IllegalArgumentException("Name cannot be null");
        }

        $this->id = $documentId;
        $this->name = $name;

        $this->timeSeries = new TimeSeriesOperation();
        $this->timeSeries->setName($name);
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getChangeVector(): ?string
    {
        return null;
    }

    abstract public function getType(): ?CommandType;

    public function getTimeSeries(): ?TimeSeriesOperation
    {
        return $this->timeSeries;
    }

    public function serialize(?DocumentConventions $conventions): array
    {
        $data = [];

        $data['Id'] = $this->id;
        $data['TimeSeries'] = $this->timeSeries->serialize($conventions);
        $data['Type'] = $this->getType()->getValue();

        if ($this->fromEtl != null) {
            $data['FromEtl'] = $this->fromEtl;
        }

        return $data;
    }

    public function onBeforeSaveChanges(?InMemoryDocumentSessionOperations $session): void
    {
        // TODO: Implement onBeforeSaveChanges() method.
    }
}

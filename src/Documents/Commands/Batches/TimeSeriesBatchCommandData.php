<?php

namespace RavenDB\Documents\Commands\Batches;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\TimeSeries\AppendOperation;
use RavenDB\Documents\Operations\TimeSeries\DeleteOperation;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesOperation;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Exceptions\IllegalArgumentException;

class TimeSeriesBatchCommandData implements CommandDataInterface
{
    private ?string $id = null;
    private ?string $name = null;
    private ?TimeSeriesOperation $timeSeries = null;

    /**
     * @param string|null $documentId
     * @param string|null $name
     * @param array<AppendOperation> $appends
     * @param array<DeleteOperation> $deletes
     *
     */
    public function __construct(?string $documentId, ?string $name, ?array $appends, ?array $deletes)
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

        if ($appends != null) {
            /** @var AppendOperation $appendOperation */
            foreach ($appends as $appendOperation) {
                $this->timeSeries->append($appendOperation);
            }
        }

        if ($deletes != null) {
            /** @var DeleteOperation $deleteOperation */
            foreach ($deletes as $deleteOperation) {
                $this->timeSeries->delete($deleteOperation);
            }
        }
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

    public function getTimeSeries(): ?TimeSeriesOperation
    {
        return $this->timeSeries;
    }

    public function setTimeSeries(?TimeSeriesOperation $timeSeries): void
    {
        $this->timeSeries = $timeSeries;
    }

    public function getChangeVector(): ?string
    {
        return null;
    }

    public function getType(): ?CommandType
    {
        return CommandType::timeSeries();
    }

    public function serialize(?DocumentConventions $conventions): array
    {
        $data = [];

        $data["Id"] = $this->id;
        $data["TimeSeries"]= $this->timeSeries->serialize($conventions);
        $data["Type"] = "TimeSeries";

        return $data;
    }

    public function onBeforeSaveChanges(?InMemoryDocumentSessionOperations $session): void
    {
        // TODO: Implement onBeforeSaveChanges() method.
    }
}

<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Primitives\NetISO8601Utils;

class TimeSeriesOperation
{
    /** @var array<AppendOperation> */
    private ?array $appends = null;
    /** @var array<DeleteOperation> */
    private ?array $deletes = null;
    /** @var array<IncrementOperation>|null  */
    private ?array $increments = null;
    private ?string $name = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function __construct(?string $name = null)
    {
        $this->name = $name;
    }

    public function serialize(?DocumentConventions $conventions): array
    {
        $data = [];

        $data["Name"] = $this->name;

        $data["Appends"] = null;
        if (!empty($this->appends)) {
            $data["Appends"] = [];
            /** @var AppendOperation $append */
            foreach ($this->appends as $append) {
                $data["Appends"][] = $append->serialize($conventions);
            }
        }

        $data["Deletes"] = null;
        if (!empty($this->deletes)) {
            $data["Deletes"] = [];
            /** @var DeleteOperation $delete */
            foreach ($this->deletes as $delete) {
                $data["Deletes"][] = $delete->serialize($conventions);
            }
        }

        $data["Increments"] = null;
        if (!empty($this->increments)) {
            $data["Increments"] = [];
            /** @var IncrementOperation $increment */
            foreach ($this->increments as $increment) {
                $data['Increments'][] = $increment->serialize($conventions);
            }
        }

        return $data;
    }

    public function increment(IncrementOperation $incrementOperation): void
    {
        if ($this->increments == null) {
            $this->increments = []; // new TreeSet<>(Comparator.comparing(x -> x.getTimestamp().getTime()));
        }

        $timestamp = NetISO8601Utils::format($incrementOperation->getTimestamp());
        $this->appends[$timestamp] = $incrementOperation;
    }

    public function append(AppendOperation $appendOperation): void
    {
        if ($this->appends == null) {
            $this->appends = []; //new TreeSet<>(Comparator.comparing(x -> x.getTimestamp().getTime()));
        }

        $timestamp = NetISO8601Utils::format($appendOperation->getTimestamp());
        $this->appends[$timestamp] = $appendOperation;
    }

    public function delete(DeleteOperation $deleteOperation): void
    {
        if ($this->deletes == null) {
            $this->deletes = [];
        }
        $this->deletes[] = $deleteOperation;
    }
}

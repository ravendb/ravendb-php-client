<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use DateTime;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Primitives\NetISO8601Utils;

class IncrementOperation
{
     private ?DateTime $timestamp = null;
     private array $values = [];

    public function getTimestamp(): ?DateTime
    {
        return $this->timestamp;
    }

    public function setTimestamp(?DateTime $timestamp): void
    {
        $this->timestamp = $timestamp;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function setValues(array $values): void
    {
        $this->values = $values;
    }

    public function serialize(?DocumentConventions $conventions): array
    {
        $data = [];

        $data['Timestamp'] = NetISO8601Utils::format($this->timestamp, true);
        $data['Values'] = [];
        foreach ($this->values as $value) {
            $data['Values'][] = $value;
        }

        return $data;
    }
}

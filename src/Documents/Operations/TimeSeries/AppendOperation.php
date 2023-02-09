<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use DateTimeInterface;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Primitives\NetISO8601Utils;

class AppendOperation
{
    private ?DateTimeInterface $timestamp = null;
    /** @var array<float> */
    private ?array $values = null;
    private ?string $tag = null;

    public function getTimestamp(): ?DateTimeInterface
    {
        return $this->timestamp;
    }

    public function setTimestamp(?DateTimeInterface $timestamp): void
    {
        $this->timestamp = $timestamp;
    }

    public function getValues(): ?array
    {
        return $this->values;
    }

    public function setValues(?array $values): void
    {
        $this->values = $values;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function setTag(?string $tag): void
    {
        $this->tag = $tag;
    }

    public function __construct(?DateTimeInterface $timestamp = null, ?array $values = null, ?string $tag = null)
    {
        $this->timestamp = $timestamp;
        $this->values = $values;
        $this->tag = $tag;
    }

    public function serialize(?DocumentConventions $conventions): array
    {
        $data = [];
        $data['Timestamp'] = NetISO8601Utils::format($this->timestamp, true);
        $data['Values'] = [];
        foreach ($this->values as $value) {
            $data['Values'][] = $value;
        }

        if ($this->tag != null) {
            $data["Tag"] = $this->tag;
        }

        return $data;
    }
}

<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use DateTimeInterface;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Primitives\NetISO8601Utils;

class DeleteOperation
{
    private ?DateTimeInterface $from = null;
    private ?DateTimeInterface $to = null;

    public function __construct(?DateTimeInterface $from= null, ?DateTimeInterface $to = null)
    {
        $this->from = $from;
        $this->to = $to;
    }

    public function getFrom(): ?DateTimeInterface
    {
        return $this->from;
    }

    public function setFrom(?DateTimeInterface $from): void
    {
        $this->from = $from;
    }

    public function getTo(): ?DateTimeInterface
    {
        return $this->to;
    }

    public function setTo(?DateTimeInterface $to): void
    {
        $this->to = $to;
    }

    public function serialize(?DocumentConventions $conventions): array
    {
        $data = [];

        $data["From"] = $this->from != null ? NetISO8601Utils::format($this->from, true) : null;
        $data["To"] = $this->to != null ? NetISO8601Utils::format($this->to, true) : null;

        return $data;
    }
}

<?php

namespace RavenDB\Documents\Operations\Revisions;

use DateTime;

class RevisionIncludeResult
{
    private ?string $id = null;
    private ?string $changeVector = null;
    private ?DateTime $before = null;
    private ?array $revision = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getChangeVector(): ?string
    {
        return $this->changeVector;
    }

    public function setChangeVector(?string $changeVector): void
    {
        $this->changeVector = $changeVector;
    }

    public function getBefore(): ?DateTime
    {
        return $this->before;
    }

    public function setBefore(?DateTime $before): void
    {
        $this->before = $before;
    }

    public function getRevision(): ?array
    {
        return $this->revision;
    }

    public function setRevision(?array $revision): void
    {
        $this->revision = $revision;
    }
}

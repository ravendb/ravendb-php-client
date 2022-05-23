<?php

namespace RavenDB\Documents\Indexes;

use DateTimeInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;

class IndexingError
{
    /** @SerializedName ("Error") */
    private ?string $error = null;

    /** @SerializedName ("Timestamp") */
    private ?DateTimeInterface $timestamp = null;

    /** @SerializedName ("Document") */
    private ?string $document = null;

    /** @SerializedName ("Action") */
    private ?string $action = null;

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): void
    {
        $this->error = $error;
    }

    public function getTimestamp(): ?DateTimeInterface
    {
        return $this->timestamp;
    }

    public function setTimestamp(?DateTimeInterface $timestamp): void
    {
        $this->timestamp = $timestamp;
    }

    public function getDocument(): ?string
    {
        return $this->document;
    }

    public function setDocument(?string $document): void
    {
        $this->document = $document;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(?string $action): void
    {
        $this->action = $action;
    }
}

<?php

namespace RavenDB\ServerWide;

use RavenDB\Type\StringArray;
use Symfony\Component\Serializer\Annotation\SerializedName;

class DocumentsCompressionConfiguration
{
    #[SerializedName("Collections")]
    private ?StringArray $collections = null;

    #[SerializedName("CompressAllCollections")]
    private bool $compressAllCollections = false;

    #[SerializedName("CompressRevisions")]
    private bool $compressRevisions = false;

    public function __construct(bool $compressRevisions = false, bool $compressAllCollections = false, ?StringArray $collections = null)
    {
        $this->compressRevisions = $compressRevisions;
        $this->compressAllCollections = $compressAllCollections;
        $this->collections = $collections;
    }

    public function getCollections(): ?StringArray
    {
        return $this->collections;
    }

    public function setCollections(?StringArray $collections): void
    {
        $this->collections = $collections;
    }

    public function isCompressAllCollections(): bool
    {
        return $this->compressAllCollections;
    }

    public function setCompressAllCollections(bool $compressAllCollections): void
    {
        $this->compressAllCollections = $compressAllCollections;
    }

    public function isCompressRevisions(): bool
    {
        return $this->compressRevisions;
    }

    public function setCompressRevisions(bool $compressRevisions): void
    {
        $this->compressRevisions = $compressRevisions;
    }
}

<?php

namespace RavenDB\Documents\Operations\Etl;

use RavenDB\Type\StringList;
use Symfony\Component\Serializer\Annotation\SerializedName;

class Transformation
{
    #[SerializedName("Name")]
    private ?string $name = null;

    #[SerializedName("Disabled")]
    private bool $disabled = false;

    #[SerializedName("Collections")]
    private ?StringList $collections = null;

    #[SerializedName("ApplyToAllDocuments")]
    private bool $applyToAllDocuments = false;

    #[SerializedName("Script")]
    private ?string $script = null;

    public function __construct()
    {
        $this->collections = new StringList();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function setDisabled(bool $disabled): void
    {
        $this->disabled = $disabled;
    }

    public function getCollections(): ?StringList
    {
        return $this->collections;
    }

    public function setCollections(null|StringList|array $collections): void
    {
        $this->collections = is_array($collections) ? StringList::fromArray($collections) : $collections;
    }

    public function isApplyToAllDocuments(): bool
    {
        return $this->applyToAllDocuments;
    }

    public function setApplyToAllDocuments(bool $applyToAllDocuments): void
    {
        $this->applyToAllDocuments = $applyToAllDocuments;
    }

    public function getScript(): ?string
    {
        return $this->script;
    }

    public function setScript(?string $script): void
    {
        $this->script = $script;
    }
}

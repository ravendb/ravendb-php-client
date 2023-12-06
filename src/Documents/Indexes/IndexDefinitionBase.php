<?php

namespace RavenDB\Documents\Indexes;

use Symfony\Component\Serializer\Annotation\SerializedName;

class IndexDefinitionBase
{
    /** @SerializedName ("Name") */
    private ?string $name = null;

    /** @SerializedName ("Priority") */
    private ?IndexPriority $priority = null;

    /** @SerializedName ("State") */
    private ?IndexState $state = null;

    public function __construct()
    {
    }

    /**
     * This is the means by which the outside world refers to this index definition
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * This is the means by which the outside world refers to this index definition
     *
     * @param string|null $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getPriority(): ?IndexPriority
    {
        return $this->priority;
    }

    public function setPriority(?IndexPriority $priority): void
    {
        $this->priority = $priority;
    }

    public function getState(): ?IndexState
    {
        return $this->state;
    }

    public function setState(?IndexState $state): void
    {
        $this->state = $state;
    }
}

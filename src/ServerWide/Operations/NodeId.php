<?php

namespace RavenDB\ServerWide\Operations;

class NodeId
{
    private ?string $nodeTag = null;
    private ?string $nodeUrl = null;
    private ?string $responsibleNode = null;

    public function getNodeTag(): ?string
    {
        return $this->nodeTag;
    }

    public function setNodeTag(?string $nodeTag): void
    {
        $this->nodeTag = $nodeTag;
    }

    public function getNodeUrl(): ?string
    {
        return $this->nodeUrl;
    }

    public function setNodeUrl(?string $nodeUrl): void
    {
        $this->nodeUrl = $nodeUrl;
    }

    public function getResponsibleNode(): ?string
    {
        return $this->responsibleNode;
    }

    public function setResponsibleNode(?string $responsibleNode): void
    {
        $this->responsibleNode = $responsibleNode;
    }
}

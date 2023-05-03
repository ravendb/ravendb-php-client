<?php

namespace RavenDB\Documents\Queries\MoreLikeThis;

class MoreLikeThisUsingDocument extends MoreLikeThisBase
{
    private ?string $documentJson = null;

    public function __construct(?string $documentJson)
    {
        parent::__construct();

        $this->documentJson = $documentJson;
    }

    public function getDocumentJson(): ?string
    {
        return $this->documentJson;
    }

    public function setDocumentJson(?string $documentJson): void
    {
        $this->documentJson = $documentJson;
    }
}

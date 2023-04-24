<?php

namespace RavenDB\Documents\Queries\MoreLikeThis;

use Closure;

class MoreLikeThisUsingDocumentForDocumentQuery extends MoreLikeThisBase
{
    private ?Closure $forDocumentQuery = null;

    public function getForDocumentQuery(): ?Closure
    {
        return $this->forDocumentQuery;
    }

    public function setForDocumentQuery(?Closure $forDocumentQuery): void
    {
        $this->forDocumentQuery = $forDocumentQuery;
    }
}

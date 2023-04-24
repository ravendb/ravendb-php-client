<?php

namespace RavenDB\Documents\Queries\MoreLikeThis;

use Closure;

class MoreLikeThisBuilder implements MoreLikeThisOperationsInterface, MoreLikeThisBuilderForDocumentQueryInterface, MoreLikeThisBuilderBaseInterface
{
    private ?MoreLikeThisBase $moreLikeThis = null;

    public function getMoreLikeThis(): ?MoreLikeThisBase
    {
        return $this->moreLikeThis;
    }

    public function usingAnyDocument(): MoreLikeThisOperationsInterface
    {
        $this->moreLikeThis = new MoreLikeThisUsingAnyDocument();

        return $this;
    }

    public function usingDocument(null|string|Closure $documentJsonOrBuilder): MoreLikeThisOperationsInterface
    {
        if (!is_string($documentJsonOrBuilder)) {
            return $this->usingDocumentWithBuilder($documentJsonOrBuilder);
        }

        return $this->usingDocumentWithJson($documentJsonOrBuilder);
    }

    public function usingDocumentWithJson(?string $documentJson): MoreLikeThisOperationsInterface
    {
        $this->moreLikeThis = new MoreLikeThisUsingDocument($documentJson);

        return $this;
    }

    public function usingDocumentWithBuilder(?Closure $builder): MoreLikeThisOperationsInterface
    {
        $moreLikeThis = new MoreLikeThisUsingDocumentForDocumentQuery();
        $moreLikeThis->setForDocumentQuery($builder);

        $this->moreLikeThis = $moreLikeThis;

        return $this;
    }

    public function withOptions(MoreLikeThisOptions $options): MoreLikeThisOperationsInterface
    {
        $this->moreLikeThis->setOptions($options);

        return $this;
    }
}

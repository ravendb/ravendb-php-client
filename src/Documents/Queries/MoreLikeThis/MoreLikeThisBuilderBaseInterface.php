<?php

namespace RavenDB\Documents\Queries\MoreLikeThis;

interface MoreLikeThisBuilderBaseInterface
{
    function usingAnyDocument(): MoreLikeThisOperationsInterface;

    function usingDocumentWithJson(?string $documentJson): MoreLikeThisOperationsInterface;
}

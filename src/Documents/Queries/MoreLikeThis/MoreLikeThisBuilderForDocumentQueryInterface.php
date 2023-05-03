<?php

namespace RavenDB\Documents\Queries\MoreLikeThis;

use Closure;

interface MoreLikeThisBuilderForDocumentQueryInterface
{
    function usingDocumentWithBuilder(Closure $builder): MoreLikeThisOperationsInterface;
}

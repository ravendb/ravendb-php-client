<?php

namespace RavenDB\Documents\Queries\MoreLikeThis;

interface MoreLikeThisOperationsInterface
{
    function withOptions(MoreLikeThisOptions $options): MoreLikeThisOperationsInterface;
}

<?php

namespace RavenDB\Documents\Session\Tokens;

class HighlightingTokenArray extends QueryTokenList
{
    public function __construct()
    {
        parent::__construct(HighlightingToken::class);
    }
}

<?php

namespace RavenDB\Documents\Session\Tokens;

class CompareExchangeValueIncludesTokenArray extends QueryTokenList
{
    public function __construct()
    {
        parent::__construct(CompareExchangeValueIncludesToken::class);
    }
}

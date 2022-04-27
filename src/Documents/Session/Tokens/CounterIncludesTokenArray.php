<?php

namespace RavenDB\Documents\Session\Tokens;

class CounterIncludesTokenArray extends QueryTokenList
{
    public function __construct()
    {
        parent::__construct(CounterIncludesToken::class);
    }
}

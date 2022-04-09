<?php

namespace RavenDB\Documents\Session\Tokens;

class TimeSeriesIncludesTokenArray extends QueryTokenList
{
    public function __construct()
    {
        parent::__construct(TimeSeriesIncludesToken::class);
    }
}

<?php

namespace RavenDB\Documents\Session\Tokens;

// !status: DONE
class DistinctToken extends QueryToken
{
    private function __construct()
    {
    }

    public static ?DistinctToken $INSTANCE = null;

    public function writeTo(): string
    {
        return "distinct";
    }

    public function getInstance(): DistinctToken
    {
        if (DistinctToken::$INSTANCE == null) {
            DistinctToken::$INSTANCE = new DistinctToken();
        }

        return DistinctToken::$INSTANCE;
    }
}


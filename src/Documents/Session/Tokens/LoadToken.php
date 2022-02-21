<?php

namespace RavenDB\Documents\Session\Tokens;

// !status: DONE
class LoadToken extends QueryToken
{
    public string $argument;
    public string $alias;

    private function __construct(string $argument, string $alias)
    {
        $this->argument = $argument;
        $this->alias = $alias;
    }

    public static function create(string $argument, string $alias): LoadToken
    {
        return new LoadToken($argument, $alias);
    }

    public function writeTo(): string
    {
        $result = $this->argument;
        $result .= " as ";
        $result .= $this->alias;

        return $result;
    }
}

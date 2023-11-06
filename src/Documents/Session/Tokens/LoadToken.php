<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Utils\StringBuilder;

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

    public function writeTo(StringBuilder &$writer): void
    {
        $writer->append($this->argument);
        $writer->append(" as ");
        $writer->append($this->alias);
    }
}

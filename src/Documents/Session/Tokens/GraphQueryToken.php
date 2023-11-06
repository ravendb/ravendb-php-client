<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Utils\StringBuilder;

class GraphQueryToken extends QueryToken
{
    private string $query;

    public function __construct(string $query) {
        $this->query = $query;
    }


    public function writeTo(StringBuilder $writer): void
    {
        $writer->append($this->query);
    }
}

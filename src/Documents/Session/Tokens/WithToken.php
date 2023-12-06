<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Utils\StringBuilder;

class WithToken extends QueryToken
{
    private ?string $alias;
    private ?string $query;

    public function __construct(?string $alias, ?string $query)
    {
        $this->alias = $alias;
        $this->query = $query;
    }

    public function writeTo(StringBuilder &$writer): void
    {
        $writer
            ->append("with {")
            ->append($this->query)
            ->append("} as ")
            ->append($this->alias);
    }
}

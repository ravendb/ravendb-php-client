<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Utils\StringBuilder;
use RavenDB\Utils\StringUtils;

class WithEdgesToken extends QueryToken
{
    private ?string $alias = null;
    private ?string $edgeSelector = null;
    private ?string $query = null;

    public function __construct(?string $alias, ?string $edgeSelector, ?string $query)
    {
        $this->alias = $alias;
        $this->query = $query;
        $this->edgeSelector = $edgeSelector;
    }

    public function writeTo(StringBuilder &$writer): void
    {
        $writer->append("with edges(");
        $writer->append($this->edgeSelector);
        $writer->append(")");

        if (!StringUtils::isBlank($this->query)) {
            $writer->append(" {");
            $writer->append($this->query);
            $writer->append("} ");
        }

        $writer->append(" as ");
        $writer->append($this->alias);
    }
}

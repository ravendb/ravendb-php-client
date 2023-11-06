<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Utils\StringBuilder;

class GroupByCountToken extends QueryToken
{
    private ?string $fieldName;

    private function __construct(?string $fieldName = null) {
        $this->fieldName = $fieldName;
    }

    public static function create(?string $fieldName = null): GroupByCountToken
    {
        return new GroupByCountToken($fieldName);
    }

    public function writeTo(StringBuilder &$writer): void
    {
        $writer
            ->append("count()");

        if ($this->fieldName == null) {
            return;
        }

        $writer
            ->append(" as ")
            ->append($this->fieldName);
    }
}

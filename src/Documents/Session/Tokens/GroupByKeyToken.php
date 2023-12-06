<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Utils\StringBuilder;

class GroupByKeyToken extends QueryToken
{
    private ?string $projectedName = null;
    private ?string $fieldName = null;

    private function __construct(?string $fieldName, ?string $projectedName = null)
    {
        $this->fieldName = $fieldName;
        $this->projectedName = $projectedName;
    }

    public static function create(?string $fieldName, ?string $projectedName): GroupByKeyToken
    {
        return new GroupByKeyToken($fieldName, $projectedName);
    }

    public function writeTo(StringBuilder &$writer): void
    {
        $this->writeField($writer, $this->fieldName ?? "key()");

        if ($this->projectedName == null || ($this->projectedName == $this->fieldName)) {
            return;
        }

        $writer
            ->append(" as ")
            ->append($this->projectedName);
    }
}

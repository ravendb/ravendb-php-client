<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Utils\StringBuilder;

class GroupBySumToken extends QueryToken
{
    private ?string $projectedName = null;
    private ?string $fieldName = null;

    private function __construct(string $fieldName, ?string $projectedName = null)
    {
        if ($fieldName == null) {
            throw new IllegalArgumentException("fieldName cannot be null");
        }

        $this->fieldName = $fieldName;
        $this->projectedName = $projectedName;
    }

    public static function create(?string $fieldName, ?string $projectedName): GroupBySumToken
    {
        return new GroupBySumToken($fieldName, $projectedName);
    }

    public function writeTo(StringBuilder &$writer): void
    {
        $writer
            ->append("sum(")
            ->append($this->fieldName)
            ->append(')');

        if ($this->projectedName == null) {
            return;
        }

        $writer
            ->append(" as ")
            ->append($this->projectedName);
    }
}


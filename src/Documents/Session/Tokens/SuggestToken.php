<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Utils\StringBuilder;
use RavenDB\Utils\StringUtils;

class SuggestToken extends QueryToken
{
    private ?string $fieldName;
    private ?string $alias;
    private ?string $termParameterName;
    private ?string $optionsParameterName;

    private function __construct(?string $fieldName, ?string $alias, ?string $termParameterName, ?string $optionsParameterName)
    {
        if ($fieldName == null) {
            throw new IllegalArgumentException("fieldName cannot be null");
        }

        if ($termParameterName == null) {
            throw new IllegalArgumentException("termParameterName cannot be null");
        }

        $this->fieldName = $fieldName;
        $this->alias = $alias;
        $this->termParameterName = $termParameterName;
        $this->optionsParameterName = $optionsParameterName;
    }

    public static function create(?string $fieldName, ?string $alias, ?string $termParameterName, ?string $optionsParameterName): SuggestToken
    {
        return new SuggestToken($fieldName, $alias, $termParameterName, $optionsParameterName);
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function writeTo(StringBuilder & $writer): void
    {
        $writer
            ->append("suggest(")
            ->append($this->fieldName)
            ->append(", $")
            ->append($this->termParameterName);

        if ($this->optionsParameterName != null) {
            $writer
                ->append(", $")
                ->append($this->optionsParameterName);
        }

        $writer
            ->append(")");

        if (StringUtils::isBlank($this->alias) || $this->getFieldName() == $this->alias) {
            return;
        }

        $writer
            ->append(" as ")
            ->append($this->alias);
    }
}

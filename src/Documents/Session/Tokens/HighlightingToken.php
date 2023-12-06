<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Utils\StringBuilder;

class HighlightingToken extends QueryToken
{
    private ?string $fieldName = null;
    private int $fragmentLength;
    private int $fragmentCount;
    private ?string $optionsParameterName = null;

    private function __construct(string $fieldName, int $fragmentLength, int $fragmentCount, ?string $operationsParameterName)
    {
        $this->fieldName = $fieldName;
        $this->fragmentLength = $fragmentLength;
        $this->fragmentCount = $fragmentCount;
        $this->optionsParameterName = $operationsParameterName;
    }

    public static function create(string $fieldName, int $fragmentLength, int $fragmentCount, ?string $optionsParameterName): HighlightingToken
    {
        return new HighlightingToken($fieldName, $fragmentLength, $fragmentCount, $optionsParameterName);
    }

    public function writeTo(StringBuilder &$writer): void
    {
        $writer->append("highlight(");

        $this->writeField($writer, $this->fieldName);

        $writer
                ->append(",")
                ->append($this->fragmentLength)
                ->append(",")
                ->append($this->fragmentCount);

        if ($this->optionsParameterName != null) {
            $writer
                    ->append(",$")
                    ->append($this->optionsParameterName);
        }

        $writer->append(")");
    }
}

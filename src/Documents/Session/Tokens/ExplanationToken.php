<?php

namespace RavenDB\Documents\Session\Tokens;


use RavenDB\Utils\StringBuilder;

class ExplanationToken extends QueryToken
{
     private ?string $optionsParameterName = null;

    private function __construct(?string $optionsParameterName)
    {
        $this->optionsParameterName = $optionsParameterName;
    }

    public static function create(?string $optionsParameterName): ExplanationToken {
        return new ExplanationToken($optionsParameterName);
    }

    public function writeTo(StringBuilder &$writer): void
    {
        $writer->append("explanations(");
        if ($this->optionsParameterName != null) {
            $writer->append("$" . $this->optionsParameterName);
        }
        $writer->append(")");
    }
}

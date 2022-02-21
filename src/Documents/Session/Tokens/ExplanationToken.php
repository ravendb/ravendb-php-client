<?php

namespace RavenDB\Documents\Session\Tokens;

// !status: DONE
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

    public function writeTo(): string
    {
        $result = "explanations(";

        if ($this->optionsParameterName != null) {
            $result .= "$";
            $result .= $this->optionsParameterName;
        }

        $result .= ")";

        return $result;
    }
}

<?php

namespace RavenDB\Documents\Session\Tokens;


use RavenDB\Utils\StringBuilder;

class CloseSubclauseToken extends QueryToken
{
    private function __construct()
    {
    }

    private ?string $boostParameterName = null;

    public static function create(): CloseSubclauseToken
    {
        return new CloseSubclauseToken();
    }

    public function getBoostParameterName(): ?string
    {
        return $this->boostParameterName;
    }

    public function setBoostParameterName(?string $boostParameterName): void
    {
        $this->boostParameterName = $boostParameterName;
    }

    public function writeTo(StringBuilder &$writer): void
    {
        if ($this->boostParameterName != null) {
            $writer->append(", $");
            $writer->append($this->boostParameterName);
        }

        $writer->append(")");
    }
}

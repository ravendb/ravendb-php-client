<?php

namespace RavenDB\Documents\Session\Tokens;

// !status: DONE
use RavenDB\Utils\StringBuilder;

class OpenSubclauseToken extends QueryToken
{
    private function __construct()
    {
    }

    private string $boostParameterName;

    public static function create(): OpenSubclauseToken
    {
        return new OpenSubclauseToken();
    }

    public function getBoostParameterName(): string {
        return $this->boostParameterName;
    }

    public function setBoostParameterName(string $boostParameterName): void
    {
        $this->boostParameterName = $boostParameterName;
    }

    public function writeTo(StringBuilder &$writer): void
    {
        if ($this->boostParameterName != null) {
            $writer->append("boost");
        }

        $writer->append("(");
    }
}

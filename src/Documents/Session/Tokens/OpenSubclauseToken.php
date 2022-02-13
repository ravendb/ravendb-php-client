<?php

namespace RavenDB\Documents\Session\Tokens;

// !status: DONE
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

    public function writeTo(): string {
        $result = '';
        if ($this->boostParameterName != null) {
            $result .= "boost";
        }

        $result .= "(";

        return $result;
    }
}

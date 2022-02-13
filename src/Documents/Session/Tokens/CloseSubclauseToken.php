<?php

namespace RavenDB\Documents\Session\Tokens;

// !status: DONE
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

    public function writeTo(): string {
        $result = '';
        if ($this->boostParameterName != null) {
            $result .= ", $";
            $result .= $this->boostParameterName;
        }

        $result .= ")";

        return $result;
    }
}

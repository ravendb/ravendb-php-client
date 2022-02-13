<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Exceptions\IllegalArgumentException;

// !status: DONE
class CompareExchangeValueIncludesToken extends QueryToken
{
    private string $path;

    private function __construct(?string $path)
    {
        if ($path == null) {
            throw new IllegalArgumentException("Path cannot be null");
        }

        $this->path = $path;
    }

    public static function create(string $path): CompareExchangeValueIncludesToken
    {
        return new CompareExchangeValueIncludesToken($path);
    }

    public function writeTo(): string {
        $result = "cmpxchg('";
        $result .= $this->path;
        $result .= "')";

        return $result;
    }
}

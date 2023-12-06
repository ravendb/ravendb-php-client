<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Utils\StringBuilder;

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

    public function writeTo(StringBuilder &$writer): void
    {
        $writer->append("cmpxchg('");
        $writer->append($this->path);
        $writer->append("')");
    }
}

<?php

namespace RavenDB\Documents\Session\Tokens;


use RavenDB\Utils\StringBuilder;

class CounterIncludesToken extends QueryToken
{
    private string $sourcePath;
    private ?string $counterName = null;
    private bool $all;

    private function __construct(string $sourcePath, ?string $counterName, bool $all)
    {
        $this->counterName = $counterName;
        $this->all = $all;
        $this->sourcePath = $sourcePath;
    }

    public static function create(string $sourcePath, string $counterName): CounterIncludesToken
    {
        return new CounterIncludesToken($sourcePath, $counterName, false);
    }

    public static function all(string $sourcePath): CounterIncludesToken
    {
        return new CounterIncludesToken($sourcePath, null, true);
    }

    public function addAliasToPath(string $alias): void
    {
        $this->sourcePath = empty($this->sourcePath) ?
                $alias
                : $alias . "." . $this->sourcePath;
    }

    public function writeTo(StringBuilder &$writer): void
    {
        $writer->append("counters(");

        if (!empty($this->sourcePath)) {
            $writer->append($this->sourcePath);

            if (!$this->all) {
                $writer->append(", ");
            }
        }

        if (!$this->all) {
            $writer->append("'" . $this->counterName . "'");
        }

        $writer->append(")");
    }
}

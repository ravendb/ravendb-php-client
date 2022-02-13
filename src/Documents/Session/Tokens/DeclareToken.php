<?php

namespace RavenDB\Documents\Session\Tokens;

// !status: DONE
class DeclareToken extends QueryToken
{
    private ?string $name = null;
    private ?string $parameters = null;
    private ?string $body = null;
    private bool $timeSeries = false;

    private function __construct(?string $name, ?string $body, ?string $parameters, bool $timeSeries) {
        $this->name = $name;
        $this->body = $body;
        $this->parameters = $parameters;
        $this->timeSeries = $timeSeries;
    }

    public static function createFunction(?string $name, ?string $body, ?string $parameters = null): DeclareToken
    {
        return new DeclareToken($name, $body, $parameters, false);
    }

    public static function createTimeSeries(?string $name, ?string $body, ?string $parameters = null): DeclareToken
    {
        return new DeclareToken($name, $body, $parameters, true);
    }

    public function writeTo(): string
    {
        $result = 'declare ';
        $result .= $this->timeSeries ? "timeseries " : "function ";
        $result .= $this->name;
        $result .= "(";
        $result .= $this->parameters;
        $result .= ") ";
        $result .= "{";
        $result .= PHP_EOL;
        $result .= $this->body;
        $result .= PHP_EOL;
        $result .= "}";
        $result .= PHP_EOL;

        return $result;
    }
}

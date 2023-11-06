<?php

namespace RavenDB\Documents\Session\Tokens;


use RavenDB\Utils\StringBuilder;

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

    public function writeTo(StringBuilder &$writer): void
    {
        $writer
            ->append('declare ')
            ->append($this->timeSeries ? "timeseries " : "function ")
            ->append($this->name)
            ->append("(")
            ->append($this->parameters)
            ->append(") ")
            ->append("{")
            ->append(PHP_EOL)
            ->append($this->body)
            ->append(PHP_EOL)
            ->append("}")
            ->append(PHP_EOL);
    }
}

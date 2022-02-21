<?php

namespace RavenDB\Type;

class Url
{
    private string $value;

    public function __construct(string $url)
    {
        $this->setValue($url);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $url): void
    {
        $this->value = $url;
    }

    public function getHost(): string
    {
        return parse_url($this->value, PHP_URL_HOST);
    }
}

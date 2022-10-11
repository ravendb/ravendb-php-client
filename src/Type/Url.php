<?php

namespace RavenDB\Type;

use RavenDB\Exceptions\MalformedURLException;
use RavenDB\Utils\StringUtils;

class Url implements ValueObjectInterface
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
        if (filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
            throw new MalformedURLException($url);
        }

        $this->value = $url;
    }

    public function getHost(): string
    {
        return parse_url($this->value, PHP_URL_HOST);
    }

    public function getScheme(): string
    {
        return parse_url($this->value, PHP_URL_SCHEME);
    }
}

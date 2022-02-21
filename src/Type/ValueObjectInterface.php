<?php

namespace RavenDB\Type;

interface ValueObjectInterface
{
    public function __construct(string $value);
    public function __toString(): string;
}

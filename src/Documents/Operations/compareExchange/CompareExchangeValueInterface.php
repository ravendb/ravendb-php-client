<?php

namespace RavenDB\Documents\Operations\compareExchange;

use RavenDB\Documents\Session\MetadataDictionaryInterface;

interface CompareExchangeValueInterface
{
    public function getKey(): string;

    public function getIndex(): int;

    public function setIndex(int $index): void;

    public function getValue(): object;

    public function getMetadata(): MetadataDictionaryInterface;

    public function hasMetadata(): bool;
}

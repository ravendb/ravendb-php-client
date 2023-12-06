<?php

namespace RavenDB\Documents\Operations\CompareExchange;

use RavenDB\Documents\Session\MetadataDictionaryInterface;



/**
 * @template T
 */
interface CompareExchangeValueInterface
{
    public function getKey(): ?string;

    public function getIndex(): ?int;

    public function setIndex(int $index): void;

    /**
     * @return T
     */
    public function & getValue();

    public function getMetadata(): ?MetadataDictionaryInterface;

    public function hasMetadata(): bool;
}

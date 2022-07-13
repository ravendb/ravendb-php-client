<?php

namespace RavenDB\Documents\Operations\CompareExchange;

use RavenDB\Http\HttpCache;
use RavenDB\Type\StringArray;
use RavenDB\Http\RavenCommand;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Documents\Operations\OperationInterface;
use RavenDB\Documents\Conventions\DocumentConventions;

class GetCompareExchangeValuesOperation implements OperationInterface
{
    private ?string $className = null;
    private ?StringArray $keys = null;

    private ?string $startWith = null;
    private ?int $start = null;
    private ?int $pageSize = null;

    private bool $materializeMetadata = false;

    /**
     * @param string|null              $className
     * @param array|StringArray|string $keysOrStartWith
     * @param bool|int|null            $materializeMetadataOrStart
     * @param int|null                 $pageSize
     */
    public function __construct(?string $className, $keysOrStartWith, $materializeMetadataOrStart = null, ?int $pageSize = null)
    {
        if (!is_string($keysOrStartWith)) {
            $this->initWithKeys($className, $keysOrStartWith, $materializeMetadataOrStart ?? true);
            return;
        }

        $this->initWithPagination($className, $keysOrStartWith, $materializeMetadataOrStart, $pageSize);
    }

    private function initWithKeys(?string $className, $keys, bool $materializeMetadata = true): void
    {
        if (empty($keys)) {
            throw new IllegalArgumentException('Keys cannot be null or empty array');
        }
        $this->keys                = is_array($keys) ? StringArray::fromArray($keys) : $keys;
        $this->materializeMetadata = $materializeMetadata;
        $this->className           = $className;

        $this->start     = null;
        $this->pageSize  = null;
        $this->startWith = null;
    }

    private function initWithPagination(?string $className, ?string $startWith, ?int $start = null, ?int $pageSize = null): void
    {
        $this->startWith           = $startWith;
        $this->start               = $start;
        $this->pageSize            = $pageSize;
        $this->className           = $className;
        $this->materializeMetadata = true;

        $this->keys = null;

    }

    public function getCommand(?DocumentStoreInterface $store, ?DocumentConventions $conventions, ?HttpCache $cache, bool $returnDebugInformation = false, bool $test = false): RavenCommand
    {
        return new GetCompareExchangeValuesCommand($this, $this->materializeMetadata, $conventions);
    }

    public function getKeys(): ?StringArray
    {
        return $this->keys;
    }

    public function getStartWith(): ?string
    {
        return $this->startWith;
    }

    public function getStart(): ?int
    {
        return $this->start;
    }

    public function getPageSize(): ?int
    {
        return $this->pageSize;
    }

    public function getClassName(): ?string
    {
        return $this->className;
    }
}

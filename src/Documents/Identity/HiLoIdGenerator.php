<?php

namespace RavenDB\Documents\Identity;

use DateTimeInterface;
use RavenDB\Documents\Commands\HiLoReturnCommand;
use RavenDB\Documents\Commands\NextHiLoCommand;
use RavenDB\Documents\DocumentStoreInterface;

class HiLoIdGenerator
{
    private ?DocumentStoreInterface $store = null;
    private ?string $tag = null;
    protected ?string $prefix = null;
    private int $lastBatchSize = 0;
    private ?DateTimeInterface $lastRangeDate = null;
    private ?string $dbName = null;
    private ?string $identityPartsSeparator = null;
    private RangeValue $range;
    protected ?string $serverTag = null;

    public function __construct(?string $tag, ?DocumentStoreInterface $store, ?string $dbName, ?string $identityPartsSeparator)
    {
        $this->store = $store;
        $this->tag = $tag;
        $this->dbName = $dbName;
        $this->identityPartsSeparator = $identityPartsSeparator;
        $this->range = new RangeValue(1, 0);
    }

    protected function getDocumentIdFromId(int $nextId): string
    {
        return $this->prefix . $nextId . "-" . $this->serverTag;
    }

    public function getRange(): RangeValue
    {
        return $this->range;
    }

    public function setRange(RangeValue $range): void
    {
        $this->range = $range;
    }

    /**
     * Generates the document ID.
     * @param ?object $entity Entity
     * @return ?string document id
     */
    public function generateDocumentId(?object $entity): ?string
    {
        return $this->getDocumentIdFromId($this->nextId());
    }

    public function nextId(): int
    {
        while (true) {
            // local range is not exhausted yet
            $range = $this->range;

            $id = $range->Current->incrementAndGet();
            if ($id <= $range->Max) {
                return $id;
            }

            $this->getNextRange();
        }
    }

    private function getNextRange(): void
    {
        $hiloCommand = new NextHiLoCommand($this->tag, $this->lastBatchSize, $this->lastRangeDate, $this->identityPartsSeparator, $this->range->Max);

        $re = $this->store->getRequestExecutor($this->dbName);
        $re->execute($hiloCommand);

        /** @var HiLoResult $result */
        $result = $hiloCommand->getResult();
        $this->prefix = $result->getPrefix();
        $this->serverTag = $result->getServerTag();
        $this->lastRangeDate = $result->getLastRangeAt();
        $this->lastBatchSize = $result->getLastSize();
        $this->range = new RangeValue($result->getLow(), $result->getHigh());
    }

    public function returnUnusedRange(): void
    {
        $returnCommand = new HiLoReturnCommand($this->tag, $this->range->Current->get(), $this->range->Max);

        $re = $this->store->getRequestExecutor($this->dbName);
        $re->execute($returnCommand);
    }
}

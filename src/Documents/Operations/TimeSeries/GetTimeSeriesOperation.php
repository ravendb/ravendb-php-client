<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use Closure;
use DateTimeInterface;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Operations\OperationInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpCache;
use RavenDB\Http\RavenCommand;
use RavenDB\Utils\StringUtils;

class GetTimeSeriesOperation implements OperationInterface
{
    private ?string $docId = null;
    private ?string $name = null;
    private ?int $start = null;
    private ?int $pageSize = null;
    private ?DateTimeInterface $from = null;
    private ?DateTimeInterface $to = null;
    private ?\Closure $includes = null;

     public function __construct(?string $docId, ?string $timeseries, ?DateTimeInterface $from = null, ?DateTimeInterface $to = null, int $start = 0, int $pageSize = PHP_INT_MAX, ?Closure $includes = null)
     {
        if (StringUtils::isEmpty($docId)) {
            throw new IllegalArgumentException("DocId cannot be null or empty");
        }
        if (StringUtils::isEmpty($timeseries)) {
            throw new IllegalArgumentException("Timeseries cannot be null or empty");
        }

        $this->docId = $docId;
        $this->start = $start;
        $this->pageSize = $pageSize;
        $this->name = $timeseries;
        $this->from = $from;
        $this->to = $to;
        $this->includes = $includes;
    }

    public function getCommand(?DocumentStoreInterface $store, ?DocumentConventions $conventions, ?HttpCache $cache, bool $returnDebugInformation = false, bool $test = false): RavenCommand
    {
        return new GetTimeSeriesCommand($this->docId, $this->name, $this->from, $this->to, $this->start, $this->pageSize, $this->includes);
    }
}

<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use Closure;
use RavenDB\Constants\PhpClient;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Operations\OperationInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpCache;
use RavenDB\Http\RavenCommand;
use RavenDB\Utils\StringUtils;

class GetMultipleTimeSeriesOperation implements OperationInterface
{
    private ?string $docId = null;
    private ?TimeSeriesRangeList $ranges = null;
    private ?int $start = null;
    private ?int $pageSize = null;
    private ?Closure $includes = null;

    public function __construct(?string $docId, null|TimeSeriesRangeList|array $ranges, int $start = 0, int $pageSize = PhpClient::INT_MAX_VALUE, ?Closure $includes = null)
    {
        if (empty($ranges)) {
            throw new IllegalArgumentException("Ranges cannot be null");
        }

        if (is_array($ranges)) {
            $ranges = TimeSeriesRangeList::fromArray($ranges);
        }

        $this->init($docId, $start, $pageSize, $includes);

        $this->ranges = $ranges;
    }

    private function init(?string $docId, int $start, int $pageSize, ?Closure $includes): void
    {
        if (StringUtils::isEmpty($docId)) {
            throw new IllegalArgumentException("DocId cannot be null or empty");
        }

        $this->docId = $docId;
        $this->start = $start;
        $this->pageSize = $pageSize;
        $this->includes = $includes;
    }

    public function getCommand(?DocumentStoreInterface $store, ?DocumentConventions $conventions, ?HttpCache $cache, bool $returnDebugInformation = false, bool $test = false): RavenCommand
    {
        return new GetMultipleTimeSeriesCommand($this->docId, $this->ranges, $this->start, $this->pageSize, $this->includes);
    }
}

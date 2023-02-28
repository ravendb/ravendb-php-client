<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use Closure;
use RavenDB\Constants\PhpClient;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Primitives\NetISO8601Utils;
use RavenDB\Utils\StringBuilder;
use RavenDB\Utils\StringUtils;

class GetMultipleTimeSeriesCommand extends RavenCommand
{
    private ?string $docId = null;
    private ?TimeSeriesRangeList $ranges = null;
    private ?int $start = null;
    private ?int $pageSize = null;
    private ?Closure $includes = null;

    public function __construct(?string $docId, null|TimeSeriesRangeList|array $ranges, int $start, int $pageSize, ?Closure $includes = null)
    {
        parent::__construct(TimeSeriesDetails::class);

        if ($docId == null) {
            throw new IllegalArgumentException("DocId cannot be null");
        }

        $this->docId = $docId;
        if ($ranges != null) {
            $this->ranges = TimeSeriesRangeList::ensure($ranges);
        }
        $this->start = $start;
        $this->pageSize = $pageSize;
        $this->includes = $includes;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        $pathBuilder = new StringBuilder($serverNode->getUrl());

            $pathBuilder
                    ->append("/databases/")
                    ->append($serverNode->getDatabase())
                    ->append("/timeseries/ranges")
                    ->append("?docId=")
                    ->append(urlEncode($this->docId));

            if ($this->start > 0) {
                $pathBuilder
                        ->append("&start=")
                        ->append($this->start);
            }

            if ($this->pageSize < PhpClient::INT_MAX_VALUE) {
                $pathBuilder
                        ->append("&pageSize=")
                        ->append($this->pageSize);
            }

            if (empty($this->ranges)) {
                throw new IllegalArgumentException("Ranges cannot be null or empty");
            }

            foreach ($this->ranges as $range) {
                if (StringUtils::isEmpty($range->getName())) {
                    throw new IllegalArgumentException("Missing name argument in TimeSeriesRange. Name cannot be null or empty");
                }

                $pathBuilder
                        ->append("&name=")
                        ->append($range->getName() ?? "")
                        ->append("&from=")
                        ->append($range->getFrom() == null ? "" : NetISO8601Utils::format($range->getFrom(), true))
                        ->append("&to=")
                        ->append($range->getTo() == null ? "" : NetISO8601Utils::format($range->getTo(), true));
            }

            if ($this->includes != null) {
                $pathBuilder->append(GetTimeSeriesCommand::addIncludesToRequest($this->includes));
            }

            return $pathBuilder->__toString();
   }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode), HttpRequest::GET);
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            return;
        }

        $this->result = $this->getMapper()->deserialize($response, $this->resultClass, 'json');
    }

    public function isReadRequest(): bool
    {
        return true;
    }

}

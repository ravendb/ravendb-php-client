<?php

namespace RavenDB\Documents\Operations\TimeSeries;

use Closure;
use DateTimeInterface;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Session\Loaders\TimeSeriesIncludeBuilder;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Primitives\NetISO8601Utils;

class GetTimeSeriesCommand extends RavenCommand
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
        parent::__construct(TimeSeriesRangeResult::class);

        $this->docId = $docId;
        $this->name = $timeseries;
        $this->start = $start;
        $this->pageSize = $pageSize;
        $this->from = $from;
        $this->to = $to;
        $this->includes = $includes;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        $path = $serverNode->getUrl();

        $path .= "/databases/";
        $path .= $serverNode->getDatabase();
        $path .= "/timeseries";
        $path .= "?docId=";
        $path .= urlEncode($this->docId);

        if ($this->start > 0) {
            $path .= "&start=";
            $path .= $this->start;
        }

        if ($this->pageSize < PHP_INT_MAX) {
            $path .= "&pageSize=";
            $path .= $this->pageSize;
        }

        $path .= "&name=";
        $path .= urlEncode($this->name);

        if ($this->from != null) {
            $path .= "&from=";
            $path .= NetISO8601Utils::format($this->from, true);
        }

        if ($this->to != null) {
            $path .= "&to=";
            $path .= NetISO8601Utils::format($this->to, true);
        }

        if ($this->includes != null) {
            $path .= self::addIncludesToRequest($this->includes);
        }

        return $path;
    }

    public static function addIncludesToRequest(Closure $includes): string
    {
        $includeBuilder = new TimeSeriesIncludeBuilder(DocumentConventions::getDefaultConventions());
        $includes($includeBuilder);

        $path = '';

        if ($includeBuilder->includeTimeSeriesDocument) {
            $path .= "&includeDocument=true";
        }

        if ($includeBuilder->includeTimeSeriesTags) {
            $path .= "&includeTags=true";
        }
        return $path;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode));
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

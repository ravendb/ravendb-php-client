<?php

namespace RavenDB\Documents\Commands;

use DateTimeInterface;
use RavenDB\Documents\Identity\HiLoResult;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Primitives\NetISO8601Utils;

class NextHiLoCommand extends RavenCommand
{
    private ?string $tag = null;
    private int $lastBatchSize = 0;
    private ?DateTimeInterface $lastRangeAt = null;
    private ?string $identityPartsSeparator = null;
    private ?int $lastRangeMax = null;

    public function __construct(?string $tag, int $lastBatchSize, ?DateTimeInterface $lastRangeAt, ?string $identityPartsSeparator, ?int $lastRangeMax)
    {
        parent::__construct(HiLoResult::class);

        if ($tag == null) {
            throw new IllegalArgumentException("tag cannot be null");
        }

        $this->tag = $tag;
        $this->lastBatchSize = $lastBatchSize;
        $this->lastRangeAt = $lastRangeAt;
        $this->identityPartsSeparator = $identityPartsSeparator;
        $this->lastRangeMax = $lastRangeMax;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        $date = $this->lastRangeAt != null ? NetISO8601Utils::format($this->lastRangeAt, true) : "";
        $path = "/hilo/next?tag=" . urlEncode($this->tag)
                . "&lastBatchSize=" . $this->lastBatchSize
                . "&lastRangeAt=" . $date
                . "&identityPartsSeparator=" . urlEncode($this->identityPartsSeparator)
                . "&lastMax=" . $this->lastRangeMax;

        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . $path;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $url = $this->createUrl($serverNode);
        return new HttpRequest($url);
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        $this->result = $this->getMapper()->deserialize($response, $this->getResultClass(), 'json');
    }

    public function isReadRequest(): bool
    {
        return true;
    }
}

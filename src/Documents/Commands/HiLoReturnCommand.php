<?php

namespace RavenDB\Documents\Commands;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\ServerNode;
use RavenDB\Http\VoidRavenCommand;

class HiLoReturnCommand extends VoidRavenCommand
{
    private ?string $tag = null;
    private ?int $last = null;
    private ?int $end = null;

    public function __construct(?string $tag, int $last, int $end)
    {
        parent::__construct();

        if ($last < 0) {
            throw new IllegalArgumentException("last is < 0");
        }

        if ($end < 0) {
            throw new IllegalArgumentException("end is < 0");
        }

        if ($tag == null) {
            throw new IllegalArgumentException("tag cannot be null");
        }

        $this->tag = $tag;
        $this->last = $last;
        $this->end = $end;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl()
            . "/databases/" . $serverNode->getDatabase()
            . "/hilo/return?tag=" . $this->tag
            . "&end=" . $this->end
            . "&last=" . $this->last;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode), HttpRequest::PUT);
    }
}

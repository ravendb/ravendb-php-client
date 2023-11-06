<?php

namespace RavenDB\ServerWide\Commands;

use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Type\Duration;
use RavenDB\Utils\StringUtils;

class GetTcpInfoCommand extends RavenCommand
{
    private ?string $tag = null;
    private ?string $dbName = null;
    private ?ServerNode $requestedNode = null;

    public function __construct(?string $tag, ?string $dbName = null)
    {
        parent::__construct(TcpConnectionInfo::class);
        $this->tag = $tag;
        $this->dbName = $dbName;
        $this->timeout = Duration::ofSeconds(15);
    }

    public function isReadRequest(): bool
    {
        return true;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        if (StringUtils::isEmpty($this->dbName)) {
            return $serverNode->getUrl() . "/info/tcp?tcp=" . $this->tag;
        } else {
            return $serverNode->getUrl() . "/databases/" . $this->dbName . "/info/tcp?tag=" . $this->tag;
        }
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $this->requestedNode = $serverNode;
        return new HttpRequest($this->createUrl($serverNode), HttpRequest::GET);
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            $this->throwInvalidResponse();
        }

        $this->result = $this->getMapper()->deserialize($response, TcpConnectionInfo::class, 'json');
    }

    public function getRequestedNode(): ?ServerNode
    {
        return $this->requestedNode;
    }

    public function setRequestedNode(?ServerNode $requestedNode): void
    {
        $this->requestedNode = $requestedNode;
    }
}

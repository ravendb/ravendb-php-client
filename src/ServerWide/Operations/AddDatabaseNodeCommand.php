<?php

namespace RavenDB\ServerWide\Operations;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RaftCommandInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Utils\RaftIdGenerator;
use RavenDB\Utils\StringUtils;

class AddDatabaseNodeCommand extends RavenCommand implements RaftCommandInterface
{
    private ?string $databaseName = null;
    private ?string $node = null;

    public function __construct(?string $databaseName, ?string $node)
    {
        parent::__construct(DatabasePutResult::class);

        if (StringUtils::isBlank($databaseName)) {
            throw new IllegalArgumentException("DatabaseName cannot be null");
        }

        $this->databaseName = $databaseName;
        $this->node = $node;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        $url = $serverNode->getUrl() . "/admin/databases/node?name=" . $this->databaseName;
//        if ($node != null) {
            $url .= "&node=" . ($this->node ?? 'null');
//        }

        return $url;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode), HttpRequest::PUT);
    }


    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            $this->throwInvalidResponse();
        }

        $this->result = $this->getMapper()->deserialize($response, $this->resultClass, 'json');
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function getRaftUniqueRequestId(): string
    {
        return RaftIdGenerator::newId();
    }
}

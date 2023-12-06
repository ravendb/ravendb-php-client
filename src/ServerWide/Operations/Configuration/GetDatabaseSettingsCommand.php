<?php

namespace RavenDB\ServerWide\Operations\Configuration;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;

class GetDatabaseSettingsCommand extends RavenCommand
{
    private ?string $databaseName = null;

    public function __construct(?string $databaseName)
    {
        parent::__construct(DatabaseSettings::class);

        if (empty($databaseName)) {
            throw new IllegalArgumentException('DatabaseName cannot be null');
        }
        $this->databaseName = $databaseName;
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/databases/" . $this->databaseName . "/admin/record";
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return  new HttpRequest($this->createUrl($serverNode), HttpRequest::GET);
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            $this->result = null;
            return;
        }

        $this->result = $this->getMapper()->deserialize($response, DatabaseSettings::class, 'json');
    }
}

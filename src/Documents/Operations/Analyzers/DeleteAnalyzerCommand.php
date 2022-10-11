<?php

namespace RavenDB\Documents\Operations\Analyzers;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RaftCommandInterface;
use RavenDB\Http\ServerNode;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\Utils\RaftIdGenerator;

class DeleteAnalyzerCommand extends VoidRavenCommand implements RaftCommandInterface
{
    private ?string $analyzerName = null;

    public function __construct(?string $analyzerName)
    {
        parent::__construct();

        if ($analyzerName == null) {
            throw new IllegalArgumentException("AnalyzerName cannot be null");
        }

        $this->analyzerName = $analyzerName;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/admin/analyzers?name=" . urlEncode($this->analyzerName);
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode), HttpRequest::DELETE);
    }

    public function getRaftUniqueRequestId(): string
    {
        return RaftIdGenerator::newId();
    }
}

<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RaftCommandInterface;
use RavenDB\Http\ServerNode;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\Utils\RaftIdGenerator;
use RavenDB\Utils\UrlUtils;

class DeleteCertificateCommand extends VoidRavenCommand implements RaftCommandInterface
{
    private string $thumbprint;

    public function __construct(?string $thumbprint)
    {
        parent::__construct();

        if ($thumbprint == null) {
            throw new IllegalArgumentException("Thumbprint cannot be null.");
        }
        $this->thumbprint = $thumbprint;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/admin/certificates?thumbprint=" . UrlUtils::escapeDataString($this->thumbprint);
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

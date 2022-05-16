<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;

class GetCertificatesCommand extends RavenCommand
{
    private ?int $start;
    private ?int $pageSize;

    public function __construct(?int $start = null, ?int $pageSize = null)
    {
        parent::__construct(CertificateDefinitionArray::class);

        $this->start = $start;
        $this->pageSize = $pageSize;
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . '/admin/certificates?start=' . $this->start . '&pageSize=' . $this->pageSize;
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

        $certificates = $this->getMapper()->deserialize($response, GetCertificatesResponse::class, 'json');
        $this->result = $certificates->getResults();
    }
}

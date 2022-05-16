<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Documents\Operations\GetCertificatesMetadataResponse;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Utils\StringUtils;
use RavenDB\Utils\UrlUtils;

class GetCertificatesMetadataCommand extends RavenCommand
{
    private ?string $name = null;

    public function __construct(?string $name = null)
    {
        parent::__construct(CertificateMetadataArray::class);

        $this->name = $name;
    }

    public function isReadRequest(): bool
    {
        return true;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        $path = $serverNode->getUrl() . '/admin/certificates?metadataOnly=true';

        if (StringUtils::isNotEmpty($this->name)) {
            $path .= '&name=' . UrlUtils::escapeDataString($this->name);
        }

        return $path;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode), HttpRequest::GET);
    }

    public function setResponse(?string $response, bool $fromCache = false): void
    {
        if ($response == null) {
            return;
        }

        $decodedResponse = $this->getMapper()->deserialize($response, GetCertificatesMetadataResponse::class, 'json');

        $this->result = $decodedResponse->getResults();
    }
}

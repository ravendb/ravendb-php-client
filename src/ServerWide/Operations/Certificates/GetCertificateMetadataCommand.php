<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Documents\Operations\GetCertificatesMetadataResponse;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Utils\UrlUtils;

class GetCertificateMetadataCommand extends RavenCommand
{
    private string $thumbprint;

    public function __construct(string $thumbprint)
    {
        parent::__construct(CertificateMetadata::class);

        $this->thumbprint = $thumbprint;
    }

    public function isReadRequest(): bool
    {
        return true;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl()
        . '/admin/certificates?thumbprint='
        . UrlUtils::escapeDataString($this->thumbprint)
        . '&metadataOnly=true';
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

        /** @var GetCertificatesMetadataResponse $decodedResponse */
        $decodedResponse = $this->getMapper()->deserialize($response, GetCertificatesMetadataResponse::class, 'json');

        $results = $decodedResponse->getResults();

        if (count($results) != 1) {
            self::throwInvalidResponse();
        }

        $this->result = $results[0];
    }
}

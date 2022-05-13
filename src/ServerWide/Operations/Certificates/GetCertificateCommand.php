<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Utils\UrlUtils;

class GetCertificateCommand extends RavenCommand
{
    private ?string $thumbprint;

    public function __construct(?string $thumbprint)
    {
        parent::__construct(CertificateDefinition::class);

        if ($thumbprint == null) {
            throw new IllegalArgumentException('Thumbprint cannot be null.');
        }
        $this->thumbprint = $thumbprint;
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . '/admin/certificates?thumbprint=' . UrlUtils::escapeDataString($this->thumbprint);
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

        $certificates = $this->getMapper()->deserialize($response, GetCertificatesResponse::class, 'json');

        if (count($certificates->getResults()) != 1) {
            self::throwInvalidResponse();
        }

        $this->result = $certificates->getResults()[0];
    }
}

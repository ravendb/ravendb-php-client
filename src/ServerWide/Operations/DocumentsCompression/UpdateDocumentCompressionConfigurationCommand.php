<?php

namespace RavenDB\ServerWide\Operations\DocumentsCompression;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RaftCommandInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\ServerWide\DocumentsCompressionConfiguration;
use RavenDB\Utils\RaftIdGenerator;

class UpdateDocumentCompressionConfigurationCommand extends RavenCommand implements RaftCommandInterface
{
    private ?DocumentsCompressionConfiguration $documentsCompressionConfiguration = null;

    public function __construct(?DocumentsCompressionConfiguration $configuration)
    {
        parent::__construct(DocumentCompressionConfigurationResult::class);

        if ($configuration == null) {
            throw new IllegalArgumentException("Configuration cannot be null");
        }

        $this->documentsCompressionConfiguration = $configuration;
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/admin/documents-compression/config";
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $options = [
            'json' => $this->getMapper()->normalize($this->documentsCompressionConfiguration),
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ];

        return new HttpRequest($this->createUrl($serverNode), HttpRequest::POST, $options);
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            $this->throwInvalidResponse();
        }

        $this->result = $this->getMapper()->deserialize($response, $this->getResultClass(), 'json');
    }

    public function getRaftUniqueRequestId(): string
    {
        return RaftIdGenerator::newId();
    }
}

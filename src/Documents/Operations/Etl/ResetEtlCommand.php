<?php

namespace RavenDB\Documents\Operations\Etl;

use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RaftCommandInterface;
use RavenDB\Http\ServerNode;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\Utils\RaftIdGenerator;
use RavenDB\Utils\StringBuilder;
use RavenDB\Utils\UrlUtils;

class ResetEtlCommand extends VoidRavenCommand implements RaftCommandInterface
{
    private ?string $configurationName = null;
    private ?string $transformationName = null;

        public function __construct(?string $configurationName, ?string $transformationName)
        {
            parent::__construct();

            $this->configurationName = $configurationName;
            $this->transformationName = $transformationName;
        }

        public function isReadRequest(): bool
        {
            return false;
        }

        public function createUrl(ServerNode $serverNode): string
        {
            $path = new StringBuilder($serverNode->getUrl());

            $path
                ->append("/databases/")
                ->append($serverNode->getDatabase())
                ->append("/admin/etl?configurationName=")
                ->append(UrlUtils::escapeDataString($this->configurationName))
                ->append("&transformationName=")
                ->append(UrlUtils::escapeDataString($this->transformationName));

            return $path->__toString();
        }

        public function createRequest(ServerNode $serverNode): HttpRequestInterface
        {
            $options = [
                'json' => [],
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ];

            return new HttpRequest($this->createUrl($serverNode), HttpRequest::RESET, $options);
        }

        public function getRaftUniqueRequestId(): string
        {
            return RaftIdGenerator::newId();
        }
}

<?php

namespace RavenDB\Documents\Operations\Revisions;

use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RaftCommandInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Utils\RaftIdGenerator;

class ConfigureRevisionsCommand extends RavenCommand implements RaftCommandInterface
{
        private ?RevisionsConfiguration $configuration = null;

        public function __construct(RevisionsConfiguration $configuration)
        {
            parent::__construct(ConfigureRevisionsOperationResult::class);
            $this->configuration = $configuration;
        }

        public function isReadRequest(): bool
        {
            return false;
        }

        public function createUrl(ServerNode $serverNode): string
        {
            return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/admin/revisions/config";
        }

        public function createRequest(ServerNode $serverNode): HttpRequestInterface
        {
            $options = [
                'json' => $this->getMapper()->normalize($this->configuration),
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

            $this->result = $this->getMapper()->deserialize($response, ConfigureRevisionsOperationResult::class, 'json');
        }

        public function getRaftUniqueRequestId(): string
        {
            return RaftIdGenerator::newId();
        }
}

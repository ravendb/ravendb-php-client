<?php

namespace RavenDB\Documents\Operations\Etl;

use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RaftCommandInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Utils\RaftIdGenerator;

class UpdateEtlCommand extends RavenCommand implements RaftCommandInterface
{
    private ?int $taskId = null;
    private ?EtlConfiguration $configuration = null;

    public function __construct(?int $taskId, ?EtlConfiguration $configuration)
    {
        parent::__construct(UpdateEtlOperationResult::class);

        $this->taskId = $taskId;
        $this->configuration = $configuration;
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/admin/etl?id=" . $this->taskId;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $options = [
            'json' => $this->getMapper()->normalize($this->configuration),
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ];

        return new HttpRequest($this->createUrl($serverNode), HttpRequest::PUT, $options);
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            self::throwInvalidResponse();
        }

        $this->result = $this->getMapper()->deserialize($response, $this->resultClass, 'json');
    }

    public function getRaftUniqueRequestId(): string
    {
        return RaftIdGenerator::newId();
    }


}

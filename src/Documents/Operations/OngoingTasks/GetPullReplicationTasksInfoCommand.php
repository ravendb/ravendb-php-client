<?php

namespace RavenDB\Documents\Operations\OngoingTasks;

use RavenDB\Documents\Operations\Replication\PullReplicationDefinitionAndCurrentConnections;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;

class GetPullReplicationTasksInfoCommand extends RavenCommand
{
    private ?int $taskId = null;

    public function __construct(int $taskId)
    {
        parent::__construct(PullReplicationDefinitionAndCurrentConnections::class);
        $this->taskId = $taskId;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/tasks/pull-replication/hub?key=" . $this->taskId;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode));
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response != null) {
            $this->result = $this->getMapper()->deserialize($response, PullReplicationDefinitionAndCurrentConnections::class, 'json');
        }
    }

    public function isReadRequest(): bool
    {
        return false;
    }
}

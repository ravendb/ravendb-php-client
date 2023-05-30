<?php

namespace RavenDB\Documents\Operations\OngoingTasks;

use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RaftCommandInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Primitives\SharpEnum;
use RavenDB\ServerWide\Operations\ModifyOngoingTaskResult;
use RavenDB\Utils\RaftIdGenerator;

class DeleteOngoingTaskCommand extends RavenCommand implements RaftCommandInterface
{
    private ?int $taskId = null;
    private ?OngoingTaskType $taskType = null;

    public function __construct(?int $taskId, ?OngoingTaskType $taskType)
    {
        parent::__construct(ModifyOngoingTaskResult::class);
        $this->taskId = $taskId;
        $this->taskType = $taskType;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/admin/tasks?id=" . $this->taskId . "&type=" . SharpEnum::value($this->taskType);
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode), HttpRequest::DELETE);
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            $this->throwInvalidResponse();
        }

        $this->result = $this->getMapper()->deserialize($response, $this->resultClass, 'json');
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function getRaftUniqueRequestId(): string
    {
        return RaftIdGenerator::newId();
    }
}

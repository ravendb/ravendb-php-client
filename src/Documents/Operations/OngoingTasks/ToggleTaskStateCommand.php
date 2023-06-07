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

class ToggleTaskStateCommand extends RavenCommand implements RaftCommandInterface
{
    private ?int $taskId = null;
    private ?string $taskName = null;
    private ?OngoingTaskType $type = null;
    private bool $disable = false;

    public function __construct(?int $taskId, ?string $taskName, ?OngoingTaskType $type, bool $disable)
    {
        parent::__construct(ModifyOngoingTaskResult::class);

        $this->taskId = $taskId;
        $this->taskName = $taskName;
        $this->type = $type;
        $this->disable = $disable;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        $url = $serverNode->getUrl() . "/databases/"
            . $serverNode->getDatabase() . "/admin/tasks/state?key="
            . $this->taskId . "&type=" . SharpEnum::value($this->type)
            . "&disable=" . ($this->disable ? "true": "false");

        if ($this->taskName != null) {
            $url .= "&taskName=" . urlEncode($this->taskName);
        }

        return $url;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode), HttpRequest::POST);
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response != null) {
            $this->result = $this->getMapper()->deserialize($response, ModifyOngoingTaskResult::class, 'json');
    }
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

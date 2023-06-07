<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Documents\Operations\OngoingTasks\OngoingTask;
use RavenDB\Documents\Operations\OngoingTasks\OngoingTaskBackup;
use RavenDB\Documents\Operations\OngoingTasks\OngoingTaskPullReplicationAsSink;
use RavenDB\Documents\Operations\OngoingTasks\OngoingTaskRavenEtlDetails;
use RavenDB\Documents\Operations\OngoingTasks\OngoingTaskReplication;
use RavenDB\Documents\Operations\OngoingTasks\OngoingTaskSqlEtlDetails;
use RavenDB\Documents\Operations\OngoingTasks\OngoingTaskSubscription;
use RavenDB\Documents\Operations\OngoingTasks\OngoingTaskType;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Primitives\SharpEnum;
use RavenDB\Utils\UrlUtils;

class GetOngoingTaskInfoCommand extends RavenCommand
{
    private ?string $taskName = null;
    private ?int $taskId = null;
    private ?OngoingTaskType $type = null;

    public function __construct(null|string|int $taskNameOrTaskId, OngoingTaskType $type)
    {
        parent::__construct(OngoingTask::class);

        if (empty($taskNameOrTaskId)) {
            throw new IllegalArgumentException('Value cannot be empty');
        }

        if (is_string($taskNameOrTaskId)) {
            $this->taskName = $taskNameOrTaskId;
            $this->taskId = 0;
        } else {
            $this->taskName = null;
            $this->taskId = $taskNameOrTaskId;
        }
        $this->type = $type;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        if ($this->taskName != null) {
            return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/task?taskName=" . UrlUtils::escapeDataString($this->taskName) . "&type=" . SharpEnum::value($this->type);
        }

        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/task?key=" . $this->taskId. "&type=" . SharpEnum::value($this->type);
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode));
    }


    public function setResponse(?string $response, bool $fromCache): void
    {
            if ($response != null) {
                switch ($this->type) {
                    case OngoingTaskType::REPLICATION:
                        $this->result = $this->getMapper()->deserialize($response, OngoingTaskReplication::class, 'json');
                        break;
                    case OngoingTaskType::RAVEN_ETL:
                        $this->result = $this->getMapper()->deserialize($response, OngoingTaskRavenEtlDetails::class, 'json');
                        break;
                    case OngoingTaskType::SQL_ETL:
                        $this->result = $this->getMapper()->deserialize($response, OngoingTaskSqlEtlDetails::class, 'json');
                        break;
                    case OngoingTaskType::BACKUP:
                        $this->result = $this->getMapper()->deserialize($response, OngoingTaskBackup::class, 'json');
                        break;
                    case OngoingTaskType::SUBSCRIPTION:
                        $this->result = $this->getMapper()->deserialize($response, OngoingTaskSubscription::class, 'json');
                        break;
                    case OngoingTaskType::PULL_REPLICATION_AS_SINK:
                        $this->result = $this->getMapper()->deserialize($response, OngoingTaskPullReplicationAsSink::class, 'json');
                        break;
                    default:
                        throw new IllegalStateException();
                }
            }
        }

    public function isReadRequest(): bool
    {
        return false;
    }
}

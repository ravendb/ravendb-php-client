<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\OngoingTasks\OngoingTaskType;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\RavenCommand;

class GetOngoingTaskInfoOperation implements MaintenanceOperationInterface
{
    private ?string $taskName = null;
    private ?int $taskId = null;
    private ?OngoingTaskType $type = null;

    public function __construct(null|string|int $taskNameOrTaskId, OngoingTaskType $type) {
        if (is_string($taskNameOrTaskId)) {
            $this->taskName = $taskNameOrTaskId;
            $this->taskId = 0;
        } else {
            $this->taskName = null;
            $this->taskId = $taskNameOrTaskId;
        }
        $this->type = $type;

        if ($type->isPullReplicationAsHub()) {
            throw new IllegalArgumentException($type->getValue() . " type is not supported. Please use GetPullReplicationTasksInfoOperation instead.");
        }
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        if ($this->taskName != null) {
            return new GetOngoingTaskInfoCommand($this->taskName, $this->type);
        }

        return new GetOngoingTaskInfoCommand($this->taskId, $this->type);

    }

}

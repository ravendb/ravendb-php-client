<?php

namespace RavenDB\Documents\Operations\OngoingTasks;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\MaintenanceOperationInterface;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Http\RavenCommand;
use RavenDB\Utils\StringUtils;

class ToggleOngoingTaskStateOperation implements MaintenanceOperationInterface
{
    private ?int $taskId = null;
    private ?string $taskName = null;
    private ?OngoingTaskType $type = null;
    private bool $disable = false;

    public function __construct(string|int $taskNameOrTaskId, ?OngoingTaskType $type, bool $disable)
    {
        if (is_string($taskNameOrTaskId)) {
            if (StringUtils::isEmpty($taskNameOrTaskId)) {
                throw new IllegalStateException("TaskName id must have a non empty value");
            }
            $this->taskName = $taskNameOrTaskId;
            $this->taskId = 0;
        } else {
            $this->taskName = null;
            $this->taskId = $taskNameOrTaskId;
        }

        $this->type = $type;
        $this->disable = $disable;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new ToggleTaskStateCommand($this->taskId, $this->taskName, $this->type, $this->disable);
    }
}

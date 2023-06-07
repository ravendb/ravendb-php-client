<?php

namespace RavenDB\Documents\Operations\OngoingTasks;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\MaintenanceOperationInterface;
use RavenDB\Http\RavenCommand;

class DeleteOngoingTaskOperation implements MaintenanceOperationInterface
{
    private ?int $taskId = null;
    private ?OngoingTaskType $taskType = null;

    public function __construct(?int $taskId, ?OngoingTaskType $taskType)
    {
        $this->taskId = $taskId;
        $this->taskType = $taskType;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new DeleteOngoingTaskCommand($this->taskId, $this->taskType);
    }
}

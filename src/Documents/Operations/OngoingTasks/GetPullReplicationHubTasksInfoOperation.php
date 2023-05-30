<?php

namespace RavenDB\Documents\Operations\OngoingTasks;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\MaintenanceOperationInterface;
use RavenDB\Http\RavenCommand;

class GetPullReplicationHubTasksInfoOperation implements MaintenanceOperationInterface
{
    private ?int $taskId = null;

    public function __construct(int $taskId)
    {
        $this->taskId = $taskId;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new GetPullReplicationTasksInfoCommand($this->taskId);
    }
}

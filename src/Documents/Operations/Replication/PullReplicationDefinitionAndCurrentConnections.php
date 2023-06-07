<?php

namespace RavenDB\Documents\Operations\Replication;

use RavenDB\Documents\Operations\OngoingTasks\OngoingTaskPullReplicationAsHubList;

class PullReplicationDefinitionAndCurrentConnections
{
    private ?PullReplicationDefinition $definition = null;
    private ?OngoingTaskPullReplicationAsHubList $ongoingTasks = null;

    public function getDefinition(): ?PullReplicationDefinition
    {
        return $this->definition;
    }

    public function setDefinition(?PullReplicationDefinition $definition): void
    {
        $this->definition = $definition;
    }

    public function getOngoingTasks(): ?OngoingTaskPullReplicationAsHubList
    {
        return $this->ongoingTasks;
    }

    public function setOngoingTasks(?OngoingTaskPullReplicationAsHubList $ongoingTasks): void
    {
        $this->ongoingTasks = $ongoingTasks;
    }
}

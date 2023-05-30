<?php

namespace RavenDB\Documents\Operations\OngoingTasks;

use RavenDB\Type\TypedList;

class OngoingTaskPullReplicationAsHubList extends TypedList
{
    protected function __construct()
    {
        parent::__construct(OngoingTaskPullReplicationAsHub::class);
    }
}

<?php

namespace RavenDB\Documents\Operations\OngoingTasks;

use RavenDB\ServerWide\Operations\NodeId;
use Symfony\Component\Serializer\Annotation\SerializedName;

class OngoingTask
{
    #[SerializedName("TaskId")]
    private ?int $taskId = null;

    #[SerializedName("TaskType")]
    private ?OngoingTaskType $taskType = null;

    #[SerializedName("ResponsibleNode")]
    private ?NodeId $responsibleNode = null;

    #[SerializedName("TaskState")]
    private ?OngoingTaskState $taskState = null;

    #[SerializedName("TaskConnectionStatus")]
    private ?OngoingTaskConnectionStatus $taskConnectionStatus = null;

    #[SerializedName("TaskName")]
    private ?string $taskName = null;

    #[SerializedName("Error")]
    private ?string $error = null;

    #[SerializedName("MentorNode")]
    private ?string $mentorNode = null;

    public function getTaskId(): ?int
    {
        return $this->taskId;
    }

    public function setTaskId(?int $taskId): void
    {
        $this->taskId = $taskId;
    }

    public function getTaskType(): ?OngoingTaskType
    {
        return $this->taskType;
    }

    public function setTaskType(?OngoingTaskType $taskType): void
    {
        $this->taskType = $taskType;
    }

    public function getResponsibleNode(): ?NodeId
    {
        return $this->responsibleNode;
    }

    public function setResponsibleNode(?NodeId $responsibleNode): void
    {
        $this->responsibleNode = $responsibleNode;
    }

    public function getTaskState(): ?OngoingTaskState
    {
        return $this->taskState;
    }

    public function setTaskState(?OngoingTaskState $taskState): void
    {
        $this->taskState = $taskState;
    }

    public function getTaskConnectionStatus(): ?OngoingTaskConnectionStatus
    {
        return $this->taskConnectionStatus;
    }

    public function setTaskConnectionStatus(?OngoingTaskConnectionStatus $taskConnectionStatus): void
    {
        $this->taskConnectionStatus = $taskConnectionStatus;
    }

    public function getTaskName(): ?string
    {
        return $this->taskName;
    }

    public function setTaskName(?string $taskName): void
    {
        $this->taskName = $taskName;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): void
    {
        $this->error = $error;
    }

    public function getMentorNode(): ?string
    {
        return $this->mentorNode;
    }

    public function setMentorNode(?string $mentorNode): void
    {
        $this->mentorNode = $mentorNode;
    }
}

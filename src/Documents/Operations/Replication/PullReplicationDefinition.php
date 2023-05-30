<?php

namespace RavenDB\Documents\Operations\Replication;

use RavenDB\Type\Duration;
use Symfony\Component\Serializer\Annotation\SerializedName;

class PullReplicationDefinition
{
    #[SerializedName("Certificates")]
    private ?array $certificates = null; // <thumbprint, base64 cert>

    #[SerializedName("DelayReplicationFor")]
    private ?Duration $delayReplicationFor = null;

    #[SerializedName("Disabled")]
    private bool $disabled = false;

    #[SerializedName("MentorNode")]
    private ?string $mentorNode = null;

    #[SerializedName("Mode")]
    private ?array $mode = null;

    #[SerializedName("Name")]
    private ?string $name = null;

    #[SerializedName("TaskId")]
    private ?int $taskId = null;

    #[SerializedName("WithFiltering")]
    private bool $withFiltering = false;

    #[SerializedName("PreventDeletionsMode")]
    private ?PreventDeletionsMode $preventDeletionsMode = null;

    public function __construct(?string $name = null)
    {
        $this->name = $name;

        // todo: check is this good representation for EnumSet
        $this->mode = [PullReplicationMode::hubToSink()];
    }

    public function getCertificates(): ?array
    {
        return $this->certificates;
    }

    public function setCertificates(?array $certificates): void
    {
        $this->certificates = $certificates;
    }

    public function getDelayReplicationFor(): ?Duration
    {
        return $this->delayReplicationFor;
    }

    public function setDelayReplicationFor(?Duration $delayReplicationFor): void
    {
        $this->delayReplicationFor = $delayReplicationFor;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function setDisabled(bool $disabled): void
    {
        $this->disabled = $disabled;
    }

    public function getMentorNode(): ?string
    {
        return $this->mentorNode;
    }

    public function setMentorNode(?string $mentorNode): void
    {
        $this->mentorNode = $mentorNode;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getTaskId(): ?int
    {
        return $this->taskId;
    }

    public function setTaskId(?int $taskId): void
    {
        $this->taskId = $taskId;
    }

    public function isWithFiltering(): bool
    {
        return $this->withFiltering;
    }

    public function setWithFiltering(bool $withFiltering): void
    {
        $this->withFiltering = $withFiltering;
    }

    public function getPreventDeletionsMode(): ?PreventDeletionsMode
    {
        return $this->preventDeletionsMode;
    }

    public function setPreventDeletionsMode(?PreventDeletionsMode $preventDeletionsMode): void
    {
        $this->preventDeletionsMode = $preventDeletionsMode;
    }


}

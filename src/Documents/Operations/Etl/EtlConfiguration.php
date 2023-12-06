<?php

namespace RavenDB\Documents\Operations\Etl;

use Symfony\Component\Serializer\Annotation\SerializedName;

abstract class EtlConfiguration
{
    #[SerializedName("TaskId")]
    private int $taskId = 0;

    #[SerializedName("Name")]
    private ?string $name = null;

    #[SerializedName("MentorNode")]
    private ?string $mentorNode = null;

    #[SerializedName("PinToMentorNode")]
    private bool $pinToMentorNode = false;

    #[SerializedName("ConnectionStringName")]
    private ?string $connectionStringName = null;

    #[SerializedName("Transforms")]
    private ?TransformationList $transforms = null;

    #[SerializedName("Disabled")]
    private bool $disabled = false;

    #[SerializedName("AllowEtlOnNonEncryptedChannel")]
    private bool $allowEtlOnNonEncryptedChannel = false;

    public function __construct()
    {
        $this->transforms =  new TransformationList();
    }

    public function getTaskId(): int
    {
        return $this->taskId;
    }

    public function setTaskId(int $taskId): void
    {
        $this->taskId = $taskId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getMentorNode(): ?string
    {
        return $this->mentorNode;
    }

    public function setMentorNode(?string $mentorNode): void
    {
        $this->mentorNode = $mentorNode;
    }

    public function isPinToMentorNode(): bool
    {
        return $this->pinToMentorNode;
    }

    public function setPinToMentorNode(bool $pinToMentorNode): void
    {
        $this->pinToMentorNode = $pinToMentorNode;
    }

    public function getConnectionStringName(): ?string
    {
        return $this->connectionStringName;
    }

    public function setConnectionStringName(?string $connectionStringName): void
    {
        $this->connectionStringName = $connectionStringName;
    }

    public function getTransforms(): ?TransformationList
    {
        return $this->transforms;
    }

    public function setTransforms(null|TransformationList|array $transforms): void
    {
        $this->transforms = is_array($transforms) ? TransformationList::fromArray($transforms) : $transforms;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function setDisabled(bool $disabled): void
    {
        $this->disabled = $disabled;
    }

    public function isAllowEtlOnNonEncryptedChannel(): bool
    {
        return $this->allowEtlOnNonEncryptedChannel;
    }

    public function setAllowEtlOnNonEncryptedChannel(bool $allowEtlOnNonEncryptedChannel): void
    {
        $this->allowEtlOnNonEncryptedChannel = $allowEtlOnNonEncryptedChannel;
    }
}

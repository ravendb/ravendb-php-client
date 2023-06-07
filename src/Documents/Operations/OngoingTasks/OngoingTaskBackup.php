<?php

namespace RavenDB\Documents\Operations\OngoingTasks;

use DateTime;
use RavenDB\Documents\Operations\Backups\BackupType;
use RavenDB\Documents\Operations\Backups\RetentionPolicy;
use RavenDB\Type\StringList;
use Symfony\Component\Serializer\Annotation\SerializedName;

class OngoingTaskBackup extends OngoingTask
{
    #[SerializedName("BackupType")]
    private ?BackupType $backupType = null;

    #[SerializedName("BackupDestinations")]
    private ?StringList $backupDestinations = null;

    #[SerializedName("LastFullBackup")]
    private ?DateTime $lastFullBackup = null;

    #[SerializedName("LastIncrementalBackup")]
    private ?DateTime $lastIncrementalBackup = null;

    #[SerializedName("OnGoingBackup")]
    private ?RunningBackup $onGoingBackup = null;

    #[SerializedName("NextBackup")]
    private ?NextBackup $nextBackup = null;

    #[SerializedName("RetentionPolicy")]
    private ?RetentionPolicy $retentionPolicy = null;

    #[SerializedName("IsEncrypted")]
    private bool $encrypted = false;

    #[SerializedName("LastExecutingNodeTag")]
    private ?string $lastExecutingNodeTag = null;

    public function __construct()
    {
        $this->setTaskType(OngoingTaskType::backup());
    }

    public function getBackupType(): ?BackupType
    {
        return $this->backupType;
    }

    public function setBackupType(?BackupType $backupType): void
    {
        $this->backupType = $backupType;
    }

    public function getBackupDestinations(): ?StringList
    {
        return $this->backupDestinations;
    }

    public function setBackupDestinations(?StringList $backupDestinations): void
    {
        $this->backupDestinations = $backupDestinations;
    }

    public function getLastFullBackup(): ?DateTime
    {
        return $this->lastFullBackup;
    }

    public function setLastFullBackup(?DateTime $lastFullBackup): void
    {
        $this->lastFullBackup = $lastFullBackup;
    }

    public function getLastIncrementalBackup(): ?DateTime
    {
        return $this->lastIncrementalBackup;
    }

    public function setLastIncrementalBackup(?DateTime $lastIncrementalBackup): void
    {
        $this->lastIncrementalBackup = $lastIncrementalBackup;
    }

    public function getOnGoingBackup(): ?RunningBackup
    {
        return $this->onGoingBackup;
    }

    public function setOnGoingBackup(?RunningBackup $onGoingBackup): void
    {
        $this->onGoingBackup = $onGoingBackup;
    }

    public function getNextBackup(): ?NextBackup
    {
        return $this->nextBackup;
    }

    public function setNextBackup(?NextBackup $nextBackup): void
    {
        $this->nextBackup = $nextBackup;
    }

    public function getRetentionPolicy(): ?RetentionPolicy
    {
        return $this->retentionPolicy;
    }

    public function setRetentionPolicy(?RetentionPolicy $retentionPolicy): void
    {
        $this->retentionPolicy = $retentionPolicy;
    }

    public function isEncrypted(): bool
    {
        return $this->encrypted;
    }

    public function setEncrypted(bool $encrypted): void
    {
        $this->encrypted = $encrypted;
    }

    public function getLastExecutingNodeTag(): ?string
    {
        return $this->lastExecutingNodeTag;
    }

    public function setLastExecutingNodeTag(?string $lastExecutingNodeTag): void
    {
        $this->lastExecutingNodeTag = $lastExecutingNodeTag;
    }
}

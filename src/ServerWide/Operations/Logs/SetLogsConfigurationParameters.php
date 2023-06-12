<?php

namespace RavenDB\ServerWide\Operations\Logs;

use RavenDB\Type\Duration;
use Symfony\Component\Serializer\Annotation\SerializedName;

class SetLogsConfigurationParameters
{
    #[SerializedName("Mode")]
    private ?LogMode $mode = null;

    #[SerializedName("RetentionTime")]
    private ?Duration $retentionTime = null;

    #[SerializedName("RetentionSize")]
    private ?int $retentionSize = null;

    #[SerializedName("Compress")]
    private bool $compress = false;

    public function __construct(?GetLogsConfigurationResult $getLogs = null)
    {
        if ($getLogs !== null) {
            $this->mode = $getLogs->getMode();
            $this->retentionTime = $getLogs->getRetentionTime();
            $this->retentionSize = $getLogs->getRetentionSize();
            $this->compress = $getLogs->isCompress();
        }
    }

    public function getMode(): ?LogMode
    {
        return $this->mode;
    }

    public function setMode(?LogMode $mode): void
    {
        $this->mode = $mode;
    }

    public function getRetentionTime(): ?Duration
    {
        return $this->retentionTime;
    }

    public function setRetentionTime(?Duration $retentionTime): void
    {
        $this->retentionTime = $retentionTime;
    }

    public function getRetentionSize(): ?int
    {
        return $this->retentionSize;
    }

    public function setRetentionSize(?int $retentionSize): void
    {
        $this->retentionSize = $retentionSize;
    }

    public function isCompress(): bool
    {
        return $this->compress;
    }

    public function setCompress(bool $compress): void
    {
        $this->compress = $compress;
    }
}

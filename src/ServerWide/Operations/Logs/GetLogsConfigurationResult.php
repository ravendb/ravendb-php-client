<?php

namespace RavenDB\ServerWide\Operations\Logs;

use RavenDB\Type\Duration;
use Symfony\Component\Serializer\Annotation\SerializedName;

class GetLogsConfigurationResult
{
    #[SerializedName("CurrentMode")]
    private ?LogMode $currentMode = null;

    #[SerializedName("Mode")]
    private ?LogMode $mode = null;

    #[SerializedName("Path")]
    private ?string $path = null;

    #[SerializedName("UseUtcTime")]
    private bool $useUtcTime = false;

    #[SerializedName("RetentionTime")]
    private ?Duration $retentionTime = null;

    #[SerializedName("RetentionSize")]
    private ?int $retentionSize = null;

    #[SerializedName("Compress")]
    private bool $compress = false;

    public function getCurrentMode(): ?LogMode
    {
        return $this->currentMode;
    }

    public function setCurrentMode(?LogMode $currentMode): void
    {
        $this->currentMode = $currentMode;
    }

    public function getMode(): ?LogMode
    {
        return $this->mode;
    }

    public function setMode(?LogMode $mode): void
    {
        $this->mode = $mode;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): void
    {
        $this->path = $path;
    }

    public function isUseUtcTime(): bool
    {
        return $this->useUtcTime;
    }

    public function setUseUtcTime(bool $useUtcTime): void
    {
        $this->useUtcTime = $useUtcTime;
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

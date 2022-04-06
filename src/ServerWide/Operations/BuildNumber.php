<?php

namespace RavenDB\ServerWide\Operations;

class BuildNumber
{
    private string $productVersion;
    private int $buildVersion;
    private string $commitHash;
    private string $fullVersion;

    public function getProductVersion(): string
    {
        return $this->productVersion;
    }

    public function setProductVersion(string $productVersion): void
    {
        $this->productVersion = $productVersion;
    }

    public function getBuildVersion(): int
    {
        return $this->buildVersion;
    }

    public function setBuildVersion(int $buildVersion): void
    {
        $this->buildVersion = $buildVersion;
    }

    public function getCommitHash(): string
    {
        return $this->commitHash;
    }

    public function setCommitHash(string $commitHash): void
    {
        $this->commitHash = $commitHash;
    }

    public function getFullVersion(): string
    {
        return $this->fullVersion;
    }

    public function setFullVersion(string $fullVersion): void
    {
        $this->fullVersion = $fullVersion;
    }
}

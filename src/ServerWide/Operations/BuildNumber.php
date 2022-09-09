<?php

namespace RavenDB\ServerWide\Operations;
use Symfony\Component\Serializer\Annotation\SerializedName;

class BuildNumber
{
    /** @SerializedName ("ProductVersion")  */
    private ?string $productVersion = null;

    /** @SerializedName ("BuildVersion")  */
    private ?int $buildVersion = null;

    /** @SerializedName ("CommitHash")  */
    private ?string $commitHash = null;

    /** @SerializedName ("FullVersion")  */
    private ?string $fullVersion = null;

    public function getProductVersion(): ?string
    {
        return $this->productVersion;
    }

    public function setProductVersion(?string $productVersion): void
    {
        $this->productVersion = $productVersion;
    }

    public function getBuildVersion(): ?int
    {
        return $this->buildVersion;
    }

    public function setBuildVersion(?int $buildVersion): void
    {
        $this->buildVersion = $buildVersion;
    }

    public function getCommitHash(): ?string
    {
        return $this->commitHash;
    }

    public function setCommitHash(?string $commitHash): void
    {
        $this->commitHash = $commitHash;
    }

    public function getFullVersion(): ?string
    {
        return $this->fullVersion;
    }

    public function setFullVersion(?string $fullVersion): void
    {
        $this->fullVersion = $fullVersion;
    }
}

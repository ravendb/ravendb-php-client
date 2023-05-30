<?php

namespace RavenDB\Documents\Operations\Etl;

use Symfony\Component\Serializer\Annotation\SerializedName;

class RavenEtlConfiguration extends EtlConfiguration
{
    #[SerializedName("LoadRequestTimeoutInSec")]
    private ?int $loadRequestTimeoutInSec = null;

    #[SerializedName("EtlType")]
    private ?EtlType $etlType = null;

    public function __construct()
    {
        parent::__construct();

        $this->etlType = EtlType::raven();
    }

    public function getLoadRequestTimeoutInSec(): ?int
    {
        return $this->loadRequestTimeoutInSec;
    }

    public function setLoadRequestTimeoutInSec(?int $loadRequestTimeoutInSec): void
    {
        $this->loadRequestTimeoutInSec = $loadRequestTimeoutInSec;
    }

    public function getEtlType(): EtlType
    {
        return $this->etlType;
    }
}

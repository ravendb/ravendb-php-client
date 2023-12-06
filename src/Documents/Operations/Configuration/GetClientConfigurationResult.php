<?php

namespace RavenDB\Documents\Operations\Configuration;

use RavenDB\Http\ResultInterface;

use Symfony\Component\Serializer\Annotation\SerializedName;

class GetClientConfigurationResult implements ResultInterface
{
    /** @SerializedName ("Etag") */
    private ?int $etag = null;

    /** @SerializedName ("Configuration") */
    private ?ClientConfiguration $configuration;

    public function getEtag(): ?int
    {
        return $this->etag;
    }

    public function setEtag(?int $etag): void
    {
        $this->etag = $etag;
    }

    public function getConfiguration(): ?ClientConfiguration
    {
        return $this->configuration;
    }

    public function setConfiguration(?ClientConfiguration $configuration): void
    {
        $this->configuration = $configuration;
    }
}

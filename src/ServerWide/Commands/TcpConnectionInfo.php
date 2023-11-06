<?php

namespace RavenDB\ServerWide\Commands;

use Symfony\Component\Serializer\Annotation\SerializedName;

class TcpConnectionInfo
{
    /** @SerializedName ("Port")  */
    private int $port = 0;
    /** @SerializedName ("Url")  */
    private ?string $url = null;
    /** @SerializedName ("Certificate")  */
    private ?string $certificate = null;
    /** @SerializedName ("Urls")  */
    private ?array $urls = null;
    /** @SerializedName ("NodeTag")  */
    private ?string $nodeTag = null;
    /** @SerializedName ("ServerId")  */
    private ?string $serverId = null;


    public function getPort(): ?int
    {
        return $this->port;
    }

    public function setPort(?int $port): void
    {
        $this->port = $port;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function getCertificate(): ?string
    {
        return $this->certificate;
    }

    public function setCertificate(?string $certificate): void
    {
        $this->certificate = $certificate;
    }

    public function getUrls(): ?array
    {
        return $this->urls;
    }

    public function setUrls(?array $urls): void
    {
        $this->urls = $urls;
    }

    public function getNodeTag(): ?string
    {
        return $this->nodeTag;
    }

    public function setNodeTag(?string $nodeTag): void
    {
        $this->nodeTag = $nodeTag;
    }

    public function getServerId(): ?string
    {
        return $this->serverId;
    }

    public function setServerId(?string $serverId): void
    {
        $this->serverId = $serverId;
    }
}

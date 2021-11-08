<?php

namespace RavenDB\Http;

use Symfony\Component\Serializer\Annotation\SerializedName;

class ClusterTopologyResponse implements ResultInterface
{
    private ?string $leader = null;

    private string $nodeTag = '';

    private ?ClusterTopology $topology = null;

    private int $etag;

    /**
     * @SerializedName("CurrentState")
     */
    private ?string $trenutnoStanje = null;

    public function getTrenutnoStanje(): ?string
    {
        return $this->trenutnoStanje;
    }

    public function setTrenutnoStanje(?string $trenutnoStanje): void
    {
        $this->trenutnoStanje = $trenutnoStanje;
    }

    // ??? ovo dalje nemam pojma sta treba ovaj setStatus da predstavlja
    // tako da cemo za sada to da zakomentarisemo

//    private Map<String, NodeStatus> status;


//    public Map<String, NodeStatus> getStatus() {
//        return status;
//    }
//
//    public void setStatus(Map<String, NodeStatus> status) {
//this.status = status;
//    }
//

    public function getLeader(): ?string
    {
        return $this->leader;
    }

    public function setLeader(?string $leader): void
    {
        $this->leader = $leader;
    }

    public function getNodeTag(): string
    {
        return $this->nodeTag;
    }

    public function setNodeTag(string $nodeTag): void
    {
        $this->nodeTag = $nodeTag;
    }

    public function getTopology(): ?ClusterTopology
    {
        return $this->topology;
    }

    public function setTopology(?ClusterTopology $topology): void
    {
        $this->topology = $topology;
    }

    public function getEtag(): int
    {
        return $this->etag;
    }

    public function setEtag(int $etag): void
    {
        $this->etag = $etag;
    }
}

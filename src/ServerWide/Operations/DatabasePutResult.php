<?php

namespace RavenDB\ServerWide\Operations;

use RavenDB\Http\ResultInterface;
use RavenDB\ServerWide\DatabaseTopology;
use RavenDB\Type\StringArray;

use Symfony\Component\Serializer\Annotation\SerializedName;

class DatabasePutResult implements ResultInterface
{
    /** @SerializedName("RaftCommandIndex") */
    private int $raftCommandIndex;

    /** @SerializedName("Name") */
    private string $name;

    /** @SerializedName("Topology") */
    private DatabaseTopology $topology;

    /** @SerializedName("NodesAddedTo") */
    private StringArray $nodesAddedTo;

    public function __construct()
    {
        $this->nodesAddedTo = new StringArray();
    }

    public function getRaftCommandIndex(): int
    {
        return $this->raftCommandIndex;
    }

    public function setRaftCommandIndex(int $raftCommandIndex): void
    {
        $this->raftCommandIndex = $raftCommandIndex;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getTopology(): DatabaseTopology
    {
        return $this->topology;
    }

    public function setTopology(DatabaseTopology $topology): void {
        $this->topology = $topology;
    }

    public function getNodesAddedTo(): StringArray
    {
        return $$this->nodesAddedTo;
    }

    public function setNodesAddedTo(StringArray $nodesAddedTo): void
    {
        $this->nodesAddedTo = $nodesAddedTo;
    }
}

<?php

namespace RavenDB\Http;

use RavenDB\Documents\Session\SessionInfo;

// move logic to class for method: RequestExecutor::chooseNodeForRequest
class NodeResolver
{
    private ServerNode $serverNode;
    private int $nodeIndex;

    public function __construct(?RavenCommand $command, ?SessionInfo $sessionInfo, NodeSelector $nodeSelector)
    {
        $this->serverNode = $nodeSelector->getPreferredNode();
        $this->nodeIndex = 0;
    }

    public function getNode(): ServerNode
    {
        return $this->serverNode;
    }

    public function getNodeIndex(): int
    {
        return $this->nodeIndex;
    }
}

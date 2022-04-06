<?php

namespace RavenDB\Http;

// !status: DONE
class CurrentIndexAndNodeAndEtag
{
    public int $currentIndex;
    public ServerNode $currentNode;
    public int $topologyEtag;

    public function __construct(int $currentIndex, ServerNode $currentNode, int $topologyEtag)
    {
        $this->currentIndex = $currentIndex;
        $this->currentNode = $currentNode;
        $this->topologyEtag = $topologyEtag;
    }
}

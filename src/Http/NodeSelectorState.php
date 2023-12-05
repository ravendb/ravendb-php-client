<?php

namespace RavenDB\Http;

use RavenDB\Utils\AtomicInteger;

class NodeSelectorState
{
    public ?Topology $topology = null;
    public ServerNodeList $nodes;
    public array $failures;
    public array $fastestRecords = [];
    public int $fastest;
    public AtomicInteger $speedTestMode;

    public function __construct(?Topology $topology)
    {
        $this->speedTestMode = new AtomicInteger(0);
        $this->topology = $topology;
        $this->nodes = $topology->getNodes();

        for ($i = 0; $i < count($this->nodes); $i++) {
            $this->failures[$i] = new AtomicInteger(0);
            $this->fastestRecords[$i] = 0;
        }
        $this->fastest = -1;

    }
}

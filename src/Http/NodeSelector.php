<?php

namespace RavenDB\Http;

use RavenDB\Exceptions\Database\DatabaseDoesNotExistException;
use RavenDB\Exceptions\RequestedNodeUnavailableException;
use RavenDB\Primitives\CleanCloseable;
use RavenDB\Primitives\Timer;
use RavenDB\Utils\StringUtils;

class NodeSelector implements CleanCloseable
{
//    private ExecutorService $executorService;
    private ?Timer $updateFastestNodeTimer = null;
    private NodeSelectorState $state;

    public function getTopology(): ?Topology
    {
        return $this->state->topology;
    }

    public function __construct(?Topology $topology)
    {
        $this->state = new NodeSelectorState($topology);
    }

    public function onFailedRequest(int $nodeIndex): void
    {
        if (($nodeIndex < 0) || $nodeIndex >= count($this->state->failures)) {
            return; // probably already changed
        }

        $this->state->failures[$nodeIndex]->incrementAndGet();
    }

    public function onUpdateTopology(?Topology $topology, bool $forceUpdate = false): bool
    {
        if ($topology == null) {
            return false;
        }

        $stateEtag = $this->state->topology->getETag() ?? 0;
        $topologyEtag = $topology->getETag() ?? 0;

        if (($stateEtag >= $topologyEtag) && !$forceUpdate) {
            return false;
        }

        $state = new NodeSelectorState($topology);

        $this->state = $state;
        return true;
    }

    /**
     * @throws DatabaseDoesNotExistException
     * @throws RequestedNodeUnavailableException
     */
    public function getRequestedNode(string $nodeTag): CurrentIndexAndNode
    {
        $state = $this->state;
        $stateFailures = $state->failures;
        $serverNodes = $state->nodes;
        $len = min(count($serverNodes), count($stateFailures));
        for ($i = 0; $i < $len; $i++) {
            if (strcmp($serverNodes[$i]->getClusterTag(), $nodeTag) == 0) {
                if (($stateFailures[$i]->get() == 0) && StringUtils::isNotEmpty($serverNodes[$i]->getUrl())) {
                    return new CurrentIndexAndNode($i, $serverNodes[$i]);
                }
                throw new RequestedNodeUnavailableException("Requested node " . $nodeTag . " is currently unavailable, please try again later.");
            }
        }

        if (count($state->nodes) == 0) {
            throw new DatabaseDoesNotExistException("There are no nodes in the topology at all");
        }
        throw new RequestedNodeUnavailableException("Could not find requested node " . $nodeTag);
    }

    /**
     * @throws DatabaseDoesNotExistException
     */
    public function getPreferredNode(): CurrentIndexAndNode
    {
        $state = $this->state;
        return self::getPreferredNodeInternal($state);
    }

    /**
     * @throws DatabaseDoesNotExistException
     */
    public static function getPreferredNodeInternal(NodeSelectorState $state): CurrentIndexAndNode
    {
        $stateFailures = $state->failures;
        $serverNodes = $state->nodes;
        $len = min(count($serverNodes), count($stateFailures));
        for ($i = 0; $i < $len; $i++) {
            if ($stateFailures[$i]->get() == 0 && StringUtils::isNotEmpty($serverNodes[$i]->getUrl())) {
                return new CurrentIndexAndNode($i, $serverNodes[$i]);
            }
        }

        return self::unlikelyEveryoneFaultedChoice($state);
    }

    /**
     * @throws DatabaseDoesNotExistException
     */
    public function getPreferredNodeWithTopology(): CurrentIndexAndNodeAndEtag
    {
        $state = $this->state;
        $preferredNode = $this->getPreferredNodeInternal($state);
        $etag = $state->topology != null ? ($state->topology->getEtag() ?? -2) : -2;
        return new CurrentIndexAndNodeAndEtag($preferredNode->currentIndex, $preferredNode->currentNode, $etag);
    }

    private static function unlikelyEveryoneFaultedChoice(NodeSelectorState $state): CurrentIndexAndNode
    {
        // if there are all marked as failed, we'll chose the first
        // one so the user will get an error (or recover :-) );
        if (count($state->nodes) == 0) {
            throw new DatabaseDoesNotExistException("There are no nodes in the topology at all");
        }

        return new CurrentIndexAndNode (0, $state->nodes[0]);
    }

    public function getNodeBySessionId(int $sessionId): CurrentIndexAndNode
    {
//        NodeSelectorState state = _state;
//
//        if (state.topology.getNodes().size() == 0) {
//            throw new AllTopologyNodesDownException("There are no nodes in the topology at all");
//        }
//
//        int index = Math.abs(sessionId % state.topology.getNodes().size());
//
//        for (int i = index; i < state.failures.length; i++) {
//            if (state.failures[i].get() == 0 && state.nodes.get(i).getServerRole() == ServerNode.Role.MEMBER) {
//                return new CurrentIndexAndNode(i, state.nodes.get(i));
//            }
//        }
//
//        for (int i = 0; i < index; i++) {
//            if (state.failures[i].get() == 0 && state.nodes.get(i).getServerRole() == ServerNode.Role.MEMBER) {
//                return new CurrentIndexAndNode(i, state.nodes.get(i));
//            }
//        }
//
        return $this->getPreferredNode();
    }

    public function getFastestNode(): CurrentIndexAndNode
    {
//        NodeSelectorState state = _state;
//        if (state.failures[state.fastest].get() == 0 && state.nodes.get(state.fastest).getServerRole() == ServerNode.Role.MEMBER) {
//            return new CurrentIndexAndNode(state.fastest, state.nodes.get(state.fastest));
//        }
//
//        // if the fastest node has failures, we'll immediately schedule
//        // another run of finding who the fastest node is, in the meantime
//        // we'll just use the server preferred node or failover as usual
//
//        switchToSpeedTestPhase();
        return $this->getPreferredNode();
    }

//    public void restoreNodeIndex(int nodeIndex) {
//        NodeSelectorState state = _state;
//        if (state.failures.length <= nodeIndex) {
//            return; // the state was changed and we no longer have it?
//        }
//
//        state.failures[nodeIndex].set(0);
//    }
//
//    protected static void throwEmptyTopology() {
//        throw new IllegalStateException("Empty database topology, this shouldn't happen.");
//    }

    private function switchToSpeedTestPhase(): void
    {
//        NodeSelectorState state = _state;
//
//        if (!state.speedTestMode.compareAndSet(0, 1)) {
//            return;
//        }
//
//        Arrays.fill(state.fastestRecords, 0);
//
//        state.speedTestMode.incrementAndGet();
    }

//    public boolean inSpeedTestPhase() {
//        return _state.speedTestMode.get() > 1;
//    }
//
//    public void recordFastest(int index, ServerNode node) {
//        NodeSelectorState state = _state;
//        int[] stateFastest = state.fastestRecords;
//
//        // the following two checks are to verify that things didn't move
//        // while we were computing the fastest node, we verify that the index
//        // of the fastest node and the identity of the node didn't change during
//        // our check
//        if (index < 0 || index >= stateFastest.length)
//            return;
//
//        if (node != state.nodes.get(index)) {
//            return;
//        }
//
//        if (++stateFastest[index] >= 10) {
//            selectFastest(state, index);
//        }
//
//        if (state.speedTestMode.incrementAndGet() <= state.nodes.size() * 10) {
//            return;
//        }
//
//        //too many concurrent speed tests are happening
//        int maxIndex = findMaxIndex(state);
//        selectFastest(state, maxIndex);
//    }
//
//    private static int findMaxIndex(NodeSelectorState state) {
//        int[] stateFastest = state.fastestRecords;
//        int maxIndex = 0;
//        int maxValue = 0;
//
//        for (int i = 0; i < stateFastest.length; i++) {
//            if (maxValue >= stateFastest[i]) {
//                continue;
//            }
//
//            maxIndex = i;
//            maxValue = stateFastest[i];
//        }
//
//        return maxIndex;
//    }
//
//    private void selectFastest(NodeSelectorState state, int index) {
//        state.fastest = index;
//        state.speedTestMode.set(0);
//
//        if (_updateFastestNodeTimer != null) {
//            _updateFastestNodeTimer.change(Duration.ofMinutes(1));
//        } else {
//            _updateFastestNodeTimer = new Timer(this::switchToSpeedTestPhase, Duration.ofMinutes(1), executorService);
//        }
//    }

    public function scheduleSpeedTest(): void
    {
        $this->switchToSpeedTestPhase();
    }

    public function close(): void
    {
        if ($this->updateFastestNodeTimer != null) {
            $this->updateFastestNodeTimer->close();
        }
    }

}

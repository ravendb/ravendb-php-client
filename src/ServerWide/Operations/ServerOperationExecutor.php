<?php

namespace RavenDB\ServerWide\Operations;

use InvalidArgumentException;
use RavenDB\Documents\DocumentStore;
use RavenDB\Documents\Operations\Operation;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Http\ClusterRequestExecutor;
use RavenDB\Http\ServerNode;
use RavenDB\Http\Topology;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\Primitives\CleanCloseable;
use RavenDB\Utils\StringUtils;

class ServerOperationExecutor implements CleanCloseable
{
    private ?ServerOperationExecutorArray $cache;
    private ?string $nodeTag;
    private DocumentStore $store;
    private ?ClusterRequestExecutor $requestExecutor;
    private ?ClusterRequestExecutor $initialRequestExecutor;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        ?DocumentStore $store = null,
        ?ClusterRequestExecutor $requestExecutor = null,
        ?ClusterRequestExecutor $initialRequestExecutor = null,
        ?ServerOperationExecutorArray $cache = null,
        ?string $nodeTag = null
    ) {
        if (empty($store)) {
            throw new InvalidArgumentException("Store cannot be null");
        }
        if (empty($requestExecutor)) {
            throw new InvalidArgumentException("RequestExecutor cannot be null");
        }
        $this->store = $store;

        $this->requestExecutor = $requestExecutor;
        $this->initialRequestExecutor = $initialRequestExecutor;
        $this->cache = $cache;
        $this->nodeTag = $nodeTag;

//        $this->store->registerEvents($this->requestExecutor);

        if ($this->nodeTag == null) {
            $this->store->addAfterCloseListener(function($sender, $event) {
                /** @var DocumentStore $store */
                $store = $sender;
                $store->getRequestExecutor()->close();
            });
        }
    }

    public function __toString()
    {
        return 'ServerOperationExecutor';
    }

    static public function forStore(DocumentStore $store): ServerOperationExecutor
    {
        return new ServerOperationExecutor(
            $store,
            self::createRequestExecutor($store),
            null,
            new ServerOperationExecutorArray(),
            null
        );
    }

    /**
     * @throws IllegalArgumentException
     * @throws IllegalStateException
     */
    public function forNode(string $nodeTag): ServerOperationExecutor
    {
        if (StringUtils::isBlank($nodeTag)) {
            throw new IllegalArgumentException('Value cannot be null or whitespace.');
        }

        if ((($nodeTag == null) && ($this->nodeTag == null)) || (strcasecmp($nodeTag, $this->nodeTag) == 0)) {
            return $this;
        }

        if ($this->store->getConventions()->isDisableTopologyUpdates()) {
            throw new IllegalStateException('Cannot switch server operation executor, because Conventions.isDisableTopologyUpdates() is set to true');
        }

        $existingValue = $this->cache->offsetGet(strtolower($nodeTag));
        if ($existingValue != false) {
            return $existingValue;
        }

        $requestExecutor = $this->initialRequestExecutor ?? $this->requestExecutor;
        $topology = $this->getTopology($requestExecutor);

        $node = null;

        /** @var ServerNode $serverNode */
        foreach ($topology->getNodes() as $serverNode) {
            if (strcasecmp($serverNode->getClusterTag(), $nodeTag) == 0) {
                $node = $serverNode;
            }
        }

        if (!$node) {
            $availableNodes = '';
            foreach ($topology->getNodes() as $serverNode) {
                if (!empty($availableNodes)) {
                    $availableNodes .= ', ';
                }
                $availableNodes .= $serverNode->getClusterTag();
            }

            throw new IllegalStateException("Could not find node '$nodeTag' in the topology. Available nodes: $availableNodes");
        }

        $clusterExecutor = ClusterRequestExecutor::createForSingleNode(
            $node->getUrl(),
            $this->store->getAuthOptions()
//            $this->getExecutorService()
        );

        return new ServerOperationExecutor($this->store, $clusterExecutor, $requestExecutor, $this->cache, $node->getClusterTag());
    }

    public function send(ServerOperationInterface $operation): ?object
    {
        $command = $operation->getCommand($this->requestExecutor->getConventions());
        $this->requestExecutor->execute($command);

        if ($command instanceof VoidRavenCommand) {
            return null;
        }

        return $command->getResult();
    }


    public function sendAsync(ServerOperationInterface  $operation): Operation
    {
        $command = $operation->getCommand($this->requestExecutor->getConventions());
        $this->requestExecutor->execute($command);

        return new ServerWideOperation(
            $this->requestExecutor,
            null,
            $this->requestExecutor->getConventions(),
            $command->getResult()->getOperationId(),
            $command->getSelectedNodeTag() ?? $command->getResult()->getOperationNodeTag()
        );
    }

    public function close(): void
    {
        if ($this->nodeTag != null) {
            return;
        }

        if ($this->requestExecutor != null) {
            $this->requestExecutor->close();
        }

        $cache = $this->cache;
        if ($cache != null) {
            /** @var ServerOperationExecutor $value */
            foreach ($cache as $value) {
                $requestExecutor = $value->requestExecutor;

                if ($requestExecutor != null) {
                    $requestExecutor->close();
                }
            }

            $cache->clear();
        }
    }

    private function getTopology(ClusterRequestExecutor $requestExecutor): Topology
    {
        $topology = null;

        try {
            $topology = $requestExecutor->getTopology();
            if ($topology == null) {
                // a bit rude way to make sure that topology has been refreshed
                // but it handles a case when first topology update failed

                $operation = new GetBuildNumberOperation();
                $command = $operation->getCommand($requestExecutor->getConventions());
                $requestExecutor->execute($command);

                $topology = $requestExecutor->getTopology();
            }
        } catch (\Throwable $exception) {
            // ignored
        }

        if ($topology == null) {
            throw new IllegalStateException('Could not fetch the toplogy.');
        }
        return $topology;
    }

    private static function createRequestExecutor(DocumentStore $store): ClusterRequestExecutor
    {
        return $store->getConventions()->isDisableTopologyUpdates() ?
                ClusterRequestExecutor::createForSingleNode(
                    $store->getUrls()->offsetGet(0),
                    $store->getAuthOptions(),
                    $store->getConventions()
                ) :
                ClusterRequestExecutor::create(
                    $store->getUrls(),
                    null,
                    $store->getAuthOptions(),
                    $store->getConventions()
                );
    }
}

<?php

namespace RavenDB\Http;

use RavenDB\Auth\AuthOptions;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Exceptions\UnsupportedOperationException;
use RavenDB\Type\Url;
use RavenDB\Type\UrlArray;

// !status: IN PROGRESS
class ClusterRequestExecutor extends RequestExecutor
{
//  private final Semaphore clusterTopologySemaphore = new Semaphore(1);

    protected function __construct(
        ?string $databaseName,
        ?AuthOptions $authOptions,
        DocumentConventions $conventions
//        ExecutorService executorService,
//        String[] initialUrls
    ) {
        parent::__construct(
            $databaseName,
            $authOptions,
            $conventions
//            executorService,
//            initialUrls
        );
    }

    /**
     * @param array|UrlArray $initialUrls
     * @param string|null $databaseName
     * @param AuthOptions|null $authOptions
     * @param DocumentConventions|null $conventions
     * @return ClusterRequestExecutor
     */
    public static function create(
        $initialUrls,
        ?string $databaseName,
        ?AuthOptions $authOptions,
//        ExecutorService executorService,
        ?DocumentConventions $conventions = null
    ): ClusterRequestExecutor {
        $executor = new ClusterRequestExecutor(
            $databaseName,
            $authOptions,
            $conventions ?? DocumentConventions::getDefaultConventions(),
//            executorService,
//            initialUrls
        );

        $executor->disableClientConfigurationUpdates = true;
//        $executor->firstTopologyUpdate = $executor->firstTopologyUpdate($initialUrls, null);


        // @todo: remove following lines
        $serverNode = new ServerNode();
        $serverNode->setDatabase($databaseName);
        $serverNode->setUrl($initialUrls[0]);

        $topology = new Topology();
        $topology->setEtag(-1);
        $topology->getNodes()->append($serverNode);

        $executor->setNodeSelector(new NodeSelector($topology));
        //-- until here

        return $executor;
    }

    public static function createForSingleNodeWithConfigurationUpdates(
        ?string $url,
        ?string $databaseName,
        ?AuthOptions $authOptions,
//        KeyStore certificate,
//        char[] keyPassword,
        DocumentConventions $conventions
    ): ClusterRequestExecutor {
        throw new UnsupportedOperationException();
    }

    public static function createForSingleNodeWithoutConfigurationUpdates(
        ?string $url,
        ?string $databaseName,
        ?AuthOptions $authOptions,
        DocumentConventions $conventions
    ): ClusterRequestExecutor {
        throw new UnsupportedOperationException();
    }

    public static function createForSingleNode(
        Url $url,
        ?AuthOptions $authOptions,
//        ExecutorService executorService,
        ?DocumentConventions $conventions = null
    ): ClusterRequestExecutor {
        $initialUrls = new UrlArray();
        $initialUrls->append($url);

        //@todo: implement following line
//        $url = $self::validateUrls($initialUrls, $certificate)[0];

//        ClusterRequestExecutor
        $executor = new ClusterRequestExecutor(
            "",
            $authOptions,
                $conventions ?? DocumentConventions::getDefaultConventions()
//            executorService,
//            $initialUrls
        );

        $serverNode = new ServerNode();
        $serverNode->setUrl($url);

        $topology = new Topology();
        $topology->setEtag(-1);

        $nodes = new ServerNodeList();
        $nodes->append($serverNode);
        $topology->setNodes($nodes);

        $nodeSelector = new NodeSelector($topology); // new NodeSelector($topology, $executorService);

        $executor->setNodeSelector($nodeSelector);
        $executor->topologyEtag = -2;
        $executor->disableClientConfigurationUpdates = true;
        $executor->disableTopologyUpdates = true;

        return $executor;
    }

//
//    @Override
//    protected void performHealthCheck(ServerNode serverNode, int nodeIndex) {
//        execute(serverNode, nodeIndex, new GetTcpInfoCommand("health-check"), false, null);
//    }
//
//    @Override
//    public CompletableFuture<Boolean> updateTopologyAsync(UpdateTopologyParameters parameters) {
//        if (parameters == null) {
//            throw new IllegalArgumentException("Parameters cannot be null");
//        }
//
//        if (_disposed) {
//            return CompletableFuture.completedFuture(false);
//        }
//
//        return CompletableFuture.supplyAsync(() -> {
//            try {
//                boolean lockTaken = clusterTopologySemaphore.tryAcquire(parameters.getTimeoutInMs(), TimeUnit.MILLISECONDS);
//                if (!lockTaken) {
//                    return false;
//                }
//            } catch (InterruptedException e) {
//                throw new RuntimeException(e);
//            }
//
//            try {
//                if (_disposed) {
//                    return false;
//                }
//
//                GetClusterTopologyCommand command = new GetClusterTopologyCommand(parameters.getDebugTag());
//                execute(parameters.getNode(), null, command, false, null);
//
//                ClusterTopologyResponse results = command.getResult();
//                List<ServerNode> nodes = ServerNode.createFrom(results.getTopology());
//
//                Topology newTopology = new Topology();
//                newTopology.setNodes(nodes);
//                newTopology.setEtag(results.getEtag());
//
//                topologyEtag = results.getEtag();
//
//                if (_nodeSelector == null) {
//                    _nodeSelector = new NodeSelector(newTopology, _executorService);
//
//                    if (getConventions().getReadBalanceBehavior() == ReadBalanceBehavior.FASTEST_NODE) {
//                        _nodeSelector.scheduleSpeedTest();
//                    }
//                } else if (_nodeSelector.onUpdateTopology(newTopology, parameters.isForceUpdate())) {
//                    disposeAllFailedNodesTimers();
//
//                    if (getConventions().getReadBalanceBehavior() == ReadBalanceBehavior.FASTEST_NODE) {
//                        _nodeSelector.scheduleSpeedTest();
//                    }
//                }
//
//                onTopologyUpdatedInvoke(newTopology);
//            } catch (Exception e) {
//                if (!_disposed) {
//                    throw e;
//                }
//            } finally {
//                clusterTopologySemaphore.release();
//            }
//
//            return true;
//        }, _executorService);
//    }
//
//    @Override
//    protected CompletableFuture<Void> updateClientConfigurationAsync(ServerNode serverNode) {
//        return CompletableFuture.completedFuture(null);
//    }

    /**
     * @throws IllegalStateException
     */
    protected function throwExceptions(?string $details): void {
        throw new IllegalStateException("Failed to retrieve cluster topology from all known nodes" . PHP_EOL . $details);
    }
}

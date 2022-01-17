<?php

namespace RavenDB\ServerWide\Operations;

use InvalidArgumentException;
use RavenDB\Documents\DocumentStore;
use RavenDB\Http\ClusterRequestExecutor;
use RavenDB\primitives\CleanCloseable;

// !status: IN PROGRESS
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

//        store.registerEvents(_requestExecutor);
//
//        if (_nodeTag == null) {
//            store.addAfterCloseListener((sender, event) -> _requestExecutor.close());
//        }
    }

    static public function forStore(DocumentStore $store): ServerOperationExecutor
    {
        return new ServerOperationExecutor(
            $store,
            self::createRequestExecutor($store),
            null,
            null,
            null
        );
    }

    public function close(): void
    {
        // TODO: Implement close() method.
    }



//        public ServerOperationExecutor forNode(String nodeTag) {
//        if (StringUtils.isBlank(nodeTag)) {
//            throw new IllegalArgumentException("Value cannot be null or whitespace.");
//        }
//
//        if ((nodeTag == null && _nodeTag == null) || _nodeTag.equalsIgnoreCase(nodeTag)) {
//            return this;
//        }
//
//        if (_store.getConventions().isDisableTopologyUpdates()) {
//            throw new IllegalStateException("Cannot switch server operation executor, because Conventions.isDisableTopologyUpdates() is set to 'true'");
//        }
//
//        return _cache.computeIfAbsent(nodeTag, tag -> {
//            ClusterRequestExecutor requestExecutor = ObjectUtils.firstNonNull(_initialRequestExecutor, _requestExecutor);
//            Topology topology = getTopology(requestExecutor);
//
//            ServerNode node = topology
//                    .getNodes()
//                    .stream()
//                    .filter(x -> tag.equalsIgnoreCase(x.getClusterTag()))
//                    .findFirst()
//                    .orElse(null);
//
//            if (node == null) {
//                String availableNodes = topology.getNodes()
//                        .stream()
//                        .map(x -> x.getClusterTag())
//                        .collect(Collectors.joining(", "));
//
//                throw new IllegalStateException("Could not find node '" + tag + "' in the topology. Available nodes: " + availableNodes);
//            }
//
//            ClusterRequestExecutor clusterExecutor = ClusterRequestExecutor.createForSingleNode(node.getUrl(),
//                    _store.getCertificate(),
//                    _store.getCertificatePrivateKeyPassword(),
//                    _store.getTrustStore(),
//                    _store.getExecutorService());
//
//            return new ServerOperationExecutor(_store, clusterExecutor, requestExecutor, _cache, node.getClusterTag());
//        });
//    }

    public function sendWithoutResult(VoidServerOperationInterface $operation): void
    {
        $command = $operation->getCommand($this->requestExecutor->getConventions());
        $this->requestExecutor->execute($command);
    }

    public function send(ServerOperationInterface $operation): object
    {
        $command = $operation->getCommand($this->requestExecutor->getConventions());
        $this->requestExecutor->execute($command);

        return $command->getResult();
    }

//    @SuppressWarnings("UnusedReturnValue")
//    public <TResult> TResult send(IServerOperation<TResult> operation) {
//        RavenCommand<TResult> command = operation.getCommand(_requestExecutor.getConventions());
//        _requestExecutor.execute(command);
//
//        return command.getResult();
//    }
//
//    public Operation sendAsync(IServerOperation<OperationIdResult> operation) {
//        RavenCommand<OperationIdResult> command = operation.getCommand(_requestExecutor.getConventions());
//
//        _requestExecutor.execute(command);
//        return new ServerWideOperation(_requestExecutor,
//                _requestExecutor.getConventions(),
//                command.getResult().getOperationId(),
//                ObjectUtils.firstNonNull(command.getSelectedNodeTag(), command.getResult().getOperationNodeTag()));
//    }
//
//    @Override
//    public void close() {
//        if (_nodeTag != null) {
//            return;
//        }
//
//        if (_requestExecutor != null) {
//            _requestExecutor.close();
//        }
//
//        ConcurrentMap<String, ServerOperationExecutor> cache = _cache;
//        if (cache != null) {
//            for (Map.Entry<String, ServerOperationExecutor> kvp : cache.entrySet()) {
//                ClusterRequestExecutor requestExecutor = kvp.getValue()._requestExecutor;
//                if (requestExecutor != null) {
//                    requestExecutor.close();
//                }
//            }
//
//            cache.clear();
//        }
//    }
//
//    private Topology getTopology(ClusterRequestExecutor requestExecutor) {
//        Topology topology = null;
//        try {
//            topology = requestExecutor.getTopology();
//            if (topology == null) {
//                // a bit rude way to make sure that topology has been refreshed
//                // but it handles a case when first topology update failed
//
//                GetBuildNumberOperation operation = new GetBuildNumberOperation();
//                RavenCommand<BuildNumber> command = operation.getCommand(requestExecutor.getConventions());
//                requestExecutor.execute(command);
//
//                topology = requestExecutor.getTopology();
//            }
//        } catch (Exception e) {
//            // ignored
//        }
//
//        if (topology == null) {
//            throw new IllegalStateException("Could not fetch the topology.");
//        }
//
//        return topology;
//    }

    private static function createRequestExecutor(DocumentStore $store): ClusterRequestExecutor
    {
        return $store->getConventions()->isDisableTopologyUpdates() ?
                ClusterRequestExecutor::createForSingleNode(
                    $store->getUrls()->offsetGet(0),
//                    $store->getCertificate(),
//                    $store->getCertificatePrivateKeyPassword(),
//                    $store->getTrustStore(),
//                    $store->getExecutorService(),
                    $store->getConventions()
                ) :
                ClusterRequestExecutor::create(
                    $store->getUrls(),
                    null,
//                    $store->getCertificate(),
//                    $store->getCertificatePrivateKeyPassword(),
//                    $store->getTrustStore(),
//                    $store->getExecutorService(),
                    $store->getConventions()
                );
    }
}

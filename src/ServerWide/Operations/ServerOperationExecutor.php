<?php

namespace RavenDB\ServerWide\Operations;

use RavenDB\Documents\DocumentStore;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\ClusterRequestExecutor;
use RavenDB\primitives\CleanCloseable;

class ServerOperationExecutor implements CleanCloseable
{
    private DocumentStore $store;
    private ?ClusterRequestExecutor $requestExecutor;
    private ?ClusterRequestExecutor $initialRequestExecutor;
    private ?ServerOperationExecutorArray $cache;
    private ?string $nodeTag;

    /**
     * @throws IllegalArgumentException
     */
    public function __construct(
        ?DocumentStore $store = null,
        ?ClusterRequestExecutor $requestExecutor = null,
        ?ClusterRequestExecutor $initialRequestExecutor = null,
        ?ServerOperationExecutorArray $cache = null,
        ?string $nodeTag = null
    ) {
        if (empty($store)) {
            throw new IllegalArgumentException("Store cannot be null");
        }
        if (empty($requestExecutor)) {
            throw new IllegalArgumentException("RequestExecutor cannot be null");
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

    function close(): void
    {
        // TODO: Implement close() method.
    }

    public function send(VoidServerOperationInterface $operation): void
    {
        $command = $operation->getCommand($this->requestExecutor->getConventions());
        $this->requestExecutor->execute($command);
    }
}

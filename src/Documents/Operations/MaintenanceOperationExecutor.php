<?php

namespace RavenDB\Documents\Operations;

use InvalidArgumentException;
use RavenDB\Documents\DocumentStore;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Http\RequestExecutor;
use RavenDB\Http\ResultInterface;
use RavenDB\ServerWide\Operations\ServerOperationExecutor;
use RavenDB\Utils\StringUtils;

// !status: IN PROGRESS
class MaintenanceOperationExecutor
{
    private DocumentStore $store;
    private string $databaseName;
    private ?RequestExecutor $requestExecutor = null;
    private ?ServerOperationExecutor $serverOperationExecutor = null;

    public function __construct(DocumentStore $store, ?string $databaseName = null)
    {
        $this->store = $store;
        $this->databaseName = $databaseName ?? $store->getDatabase();
    }

    /**
     * @throws InvalidArgumentException
     * @throws IllegalStateException
     */
    private function getRequestExecutor(): RequestExecutor
    {
        if ($this->requestExecutor == null) {
            return $this->requestExecutor =
                ($this->databaseName != null) ? $this->store->getRequestExecutor($this->databaseName) : null;
        }

        return $this->requestExecutor;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function server(): ServerOperationExecutor
    {
        if ($this->serverOperationExecutor == null) {
            $this->serverOperationExecutor = ServerOperationExecutor::forStore($this->store);
        }

        return $this->serverOperationExecutor;
    }

    public function forDatabase(?string $databaseName): MaintenanceOperationExecutor
    {
        if (StringUtils::equalsIgnoreCase($this->databaseName, $databaseName)) {
            return $this;
        }

        return new MaintenanceOperationExecutor($this->store, $databaseName);
    }

//    public function send(VoidMaintenanceOperationInterface $operation): void
//    {
//        $this->assertDatabaseNameSet();
//        $command = $operation->getCommand($this->getRequestExecutor()->getConventions());
//        $this->getRequestExecutor()->execute($command);
//    }

    /**
     * @param MaintenanceOperationInterface $operation
     *
     * @return ResultInterface|mixed|null
     */
    public function send(MaintenanceOperationInterface $operation)
    {
        $this->assertDatabaseNameSet();
        $command = $operation->getCommand($this->getRequestExecutor()->getConventions());
        $this->getRequestExecutor()->execute($command);
        return $command->getResult();
    }

//    public Operation sendAsync(IMaintenanceOperation<OperationIdResult> operation) {
//        assertDatabaseNameSet();
//        RavenCommand<OperationIdResult> command = operation.getCommand(getRequestExecutor().getConventions());
//
//        getRequestExecutor().execute(command);
//        String node =
//              ObjectUtils.firstNonNull(command.getSelectedNodeTag(), command.getResult().getOperationNodeTag());
//        return new Operation(getRequestExecutor(),
//                () -> store.changes(databaseName, node), getRequestExecutor().getConventions(),
//                command.getResult().getOperationId(),
//                node);
//    }

    private function assertDatabaseNameSet(): void
    {
        if ($this->databaseName == null) {
            throw new IllegalStateException(
              "Cannot use maintenance without a database defined, did you forget to call forDatabase?"
            );
        }
    }
}

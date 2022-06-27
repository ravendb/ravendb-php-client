<?php

namespace RavenDB\Documents\Operations;

use Throwable;
use RuntimeException;
use RavenDB\Utils\StringUtils;
use RavenDB\Http\RequestExecutor;
use RavenDB\Constants\HttpStatusCode;
use RavenDB\Documents\Session\SessionInfo;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Exceptions\IllegalArgumentException;

class OperationExecutor
{
    private ?DocumentStoreInterface $store = null;
    private ?string $databaseName = null;
    private ?RequestExecutor $requestExecutor = null;

    public function __construct(DocumentStoreInterface $store, ?string $databaseName = null)
    {
        $this->store = $store;
        $this->databaseName = $databaseName != null ? $databaseName : $store->getDatabase();
        if (StringUtils::isNotBlank($this->databaseName)) {
            $this->requestExecutor = $store->getRequestExecutor($this->databaseName);
        } else {
            throw new IllegalStateException("Cannot use operations without a database defined, did you forget to call forDatabase?");
        }
    }

    public function forDatabase(?string $databaseName): OperationExecutor
    {
        if (StringUtils::equalsIgnoreCase($this->databaseName, $databaseName)) {
            return $this;
        }

        return new OperationExecutor($this->store, $databaseName);
    }

//    public void send(IVoidOperation operation) {
//        send(operation, null);
//    }
//
//    public void send(IVoidOperation operation, SessionInfo sessionInfo = null) {
//        RavenCommand<Void> command = operation.getCommand(store, requestExecutor.getConventions(), requestExecutor.getCache());
//        requestExecutor.execute(command, sessionInfo);
//    }
//
//    public <TResult> TResult send(IOperation<TResult> operation) {
//        return send(operation, null);
//    }
//
//    public <TResult> TResult send(IOperation<TResult> operation, SessionInfo sessionInfo) {
//        RavenCommand<TResult> command = operation.getCommand(store, requestExecutor.getConventions(), requestExecutor.getCache());
//        requestExecutor.execute(command, sessionInfo);
//
//        return command.getResult();
//    }

    public function sendAsync(?OperationInterface $operation, ?SessionInfo $sessionInfo = null): Operation
    {
        $command = $operation->getCommand($this->store, $this->requestExecutor->getConventions(), $this->requestExecutor->getCache());

        $this->requestExecutor->execute($command, $sessionInfo);
        $node = $command->getSelectedNodeTag() ?? $command->getResult()->getOperationNodeTag();

        $store = $this->store;
        return new Operation(
                $this->requestExecutor,
                function () use ($node, $store) {
                    $store->changes($this->databaseName, $node);
                },
                $this->requestExecutor->getConventions(),
                $command->getResult()->getOperationId(),
                $node);
    }


    /**
     * @param mixed ...$parameters
     */
    public function send(...$parameters)
    {
        if (count($parameters) == 0) {
            throw new IllegalArgumentException('Illegal arguments');
        }

        if ($parameters[0] instanceof PatchOperation) {
            $sessionInfo = null;
            if (count($parameters) > 1) {
                if (!$parameters[1] instanceof SessionInfo) {
                    throw new IllegalArgumentException('Illegal arguments');
                }
                $sessionInfo = $parameters[1];
            }
            return $this->sendPatchOperation($parameters[0], $sessionInfo);
        }

        if (is_string($parameters[0])) {

            if (count($parameters) > 1) {
                if ($parameters[1] instanceof PatchOperation) {
                    $sessionInfo = null;
                    if (count($parameters) > 2) {
                        if (!$parameters[2] instanceof SessionInfo) {
                            throw new IllegalArgumentException('Illegal arguments');
                        }
                        $sessionInfo = $parameters[2];
                    }

                    return $this->sendEntityClass($parameters[0], $parameters[1], $sessionInfo);
                }
            }
        }

        throw new IllegalArgumentException('Illegal arguments');
    }

    protected function sendPatchOperation(?PatchOperation $operation, ?SessionInfo $sessionInfo = null): PatchStatus
    {
        $command = $operation->getCommand($this->store, $this->requestExecutor->getConventions(), $this->requestExecutor->getCache());

        $this->requestExecutor->execute($command, $sessionInfo);

        if ($command->getStatusCode() == HttpStatusCode::NOT_MODIFIED) {
            return PatchStatus::notModified();
        }

        if ($command->getStatusCode() == HttpStatusCode::NOT_FOUND) {
            return PatchStatus::documentDoesNotExist();
        }

        return $command->getResult()->getStatus();
    }

    public function sendEntityClass(string $entityClass, ?PatchOperation $operation, ?SessionInfo $sessionInfo = null): PatchOperationResult
    {
        $command = $operation->getCommand($this->store, $this->requestExecutor->getConventions(), $this->requestExecutor->getCache());

        $this->requestExecutor->execute($command, $sessionInfo);

        $result = new PatchOperationResult();

        if ($command->getStatusCode() == HttpStatusCode::NOT_MODIFIED) {
            $result->setStatus(PatchStatus::notModified());
            return $result;
        }

        if ($command->getStatusCode() == HttpStatusCode::NOT_FOUND) {
            $result->setStatus(PatchStatus::documentDoesNotExist());
            return $result;
        }

        try {
            $result->setStatus($command->getResult()->getStatus());
            $result->setDocument($this->requestExecutor->getConventions()->getEntityMapper()->denormalize($command->getResult()->getModifiedDocument(), $entityClass));
            return $result;
        } catch (Throwable $e) {
            throw new RuntimeException("Unable to read patch result: " . $e->getMessage(), $e->getCode(), $e);
        }
    }
}

<?php

namespace RavenDB\Documents\Operations;

use Closure;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Exceptions\ExceptionDispatcher;
use RavenDB\Exceptions\ExceptionSchema;
use RavenDB\Extensions\JsonExtensions;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\RequestExecutor;
use RavenDB\Primitives\OperationCancelledException;

// !status: DONE
class Operation
{
    private RequestExecutor $requestExecutor;
    private DocumentConventions $conventions;
    private ?Closure $changes = null;
    private int $id;
    private ?string $nodeTag;

    public function getId(): int
    {
        return $this->id;
    }

    public function __construct(
        RequestExecutor $requestExecutor,
        ?Closure $changes,
        DocumentConventions $conventions,
        int $id,
        ?string $nodeTag = null
    )
    {
        $this->requestExecutor = $requestExecutor;
        $this->changes = $changes;
        $this->conventions = $conventions;
        $this->id = $id;
        $this->nodeTag = $nodeTag;
    }

    private function fetchOperationsStatus(): array
    {
        $command = $this->getOperationStateCommand($this->conventions, $this->id, $this->nodeTag);
        $this->requestExecutor->execute($command);

        return $command->getResult();
    }

    protected function getOperationStateCommand(DocumentConventions $conventions, int $id, ?string $nodeTag = null): RavenCommand
    {
        return new GetOperationStateCommand($id, $nodeTag);
    }

    public function getNodeTag(): string
    {
        return $this->nodeTag;
    }

    public function setNodeTag(string $nodeTag): void
    {
        $this->nodeTag = $nodeTag;
    }

    public function waitForCompletion(): void
    {
        while (true) {
            $status = $this->fetchOperationsStatus();

            $operationStatus = $status['Status'];

            switch ($operationStatus) {
                case 'Completed':
                    return;
                case 'Canceled':
                    throw new OperationCancelledException();
                case 'Faulted':
                    $result = $status['Result'];

                    /** @var OperationCancelledException $exceptionResult */
                    $exceptionResult = JsonExtensions::getDefaultMapper()->denormalize($result, OperationCancelledException::class);
                    $schema = new ExceptionSchema();

                    $schema->setUrl($this->requestExecutor->getUrl());
                    $schema->setError($exceptionResult->getError());
                    $schema->setMessage($exceptionResult->getMessage());
                    $schema->setType($exceptionResult->getType());

                    $exception = ExceptionDispatcher::get($schema, $exceptionResult->getCode());
                    throw new $exception;
            }

            usleep(500000);
        }
    }
}

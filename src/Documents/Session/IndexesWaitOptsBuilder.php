<?php

namespace RavenDB\Documents\Session;

use RavenDB\Type\Duration;
use RavenDB\Documents\Commands\Batches\BatchOptions;
use RavenDB\Documents\Commands\Batches\IndexBatchOptions;
use RavenDB\Type\StringArray;

class IndexesWaitOptsBuilder
{
    // only this classes can access to this getOptions method
    private array $__friends = ['RavenDB\Documents\Session\InMemoryDocumentSessionOperations'];

    private InMemoryDocumentSessionOperations $inMemoryDocumentSessionOperations;

    public function __construct(InMemoryDocumentSessionOperations $inMemoryDocumentSessionOperations)
    {
        $this->inMemoryDocumentSessionOperations = $inMemoryDocumentSessionOperations;
    }

    public function getOptions(): BatchOptions
    {
        $trace = debug_backtrace();
        if(!isset($trace[1]['class']) || !in_array($trace[1]['class'], $this->__friends)) {
            trigger_error('Cannot access private method ' . __CLASS__ . '::getOptions()', E_USER_ERROR);
        }

        return $this->getOptionsInternal();
    }

    private function getOptionsInternal(): BatchOptions
    {
        if ($this->inMemoryDocumentSessionOperations->saveChangesOptions == null) {
            $this->inMemoryDocumentSessionOperations->saveChangesOptions = new BatchOptions();
        }

        if ($this->inMemoryDocumentSessionOperations->saveChangesOptions->getIndexOptions() == null) {
            $this->inMemoryDocumentSessionOperations->saveChangesOptions->setIndexOptions(new IndexBatchOptions());
        }

        return $this->inMemoryDocumentSessionOperations->saveChangesOptions;
    }

    public function withTimeout(Duration $timeout): IndexesWaitOptsBuilder
    {
        $this->getOptionsInternal()->getIndexOptions()->setWaitForIndexesTimeout($timeout);
        return $this;
    }

    public function throwOnTimeout(bool $shouldThrow): IndexesWaitOptsBuilder
    {
        $this->getOptionsInternal()->getIndexOptions()->setThrowOnTimeoutInWaitForIndexes($shouldThrow);
        return $this;
    }

    /**
     * @param mixed $indexes
     * @return $this
     */
    public function waitForIndexes(...$indexes): IndexesWaitOptsBuilder
    {
        $sa = new StringArray();
        $sa->allowNull();

        foreach ($indexes as $index) {
            if (is_array($index)) {
                $sa->appendArrayValues($index);
            } else {
                $sa->append($index);
            }
        }

        $this->getOptionsInternal()->getIndexOptions()->setWaitForSpecificIndexes($sa->getArrayCopy());
        return $this;
    }
}

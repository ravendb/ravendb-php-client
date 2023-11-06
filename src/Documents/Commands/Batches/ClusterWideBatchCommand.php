<?php

namespace RavenDB\Documents\Commands\Batches;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Session\TransactionMode;
use RavenDB\Http\RaftCommandInterface;
use RavenDB\Utils\RaftIdGenerator;
class ClusterWideBatchCommand extends SingleNodeBatchCommand implements RaftCommandInterface
{
    private ?bool $disableAtomicDocumentWrites = null;

    public function __construct(
        DocumentConventions $conventions,
        array $commands,
        ?BatchOptions $options = null,
        ?bool $disableAtomicDocumentsWrites = null
    ) {
        parent::__construct($conventions, $commands, $options, TransactionMode::clusterWide());

        $this->disableAtomicDocumentWrites = $disableAtomicDocumentsWrites;
    }

    public function isDisableAtomicDocumentWrites(): bool
    {
        return $this->disableAtomicDocumentWrites;
    }

    public function getRaftUniqueRequestId(): string
    {
        return RaftIdGenerator::newId();
    }

    protected function appendOptions(): string
    {
        $option = parent::appendOptions();

        if ($this->disableAtomicDocumentWrites !== null) {
            $option .= "&disableAtomicDocumentWrites=";
            $option .= $this->disableAtomicDocumentWrites ? "true" : "false";
        }

        return $option;
    }
}

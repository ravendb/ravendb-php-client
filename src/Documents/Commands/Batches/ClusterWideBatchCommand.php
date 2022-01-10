<?php

namespace RavenDB\Documents\Commands\Batches;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Session\TransactionMode;
use RavenDB\Http\RaftCommandInterface;
use RavenDB\Utils\RaftIdGenerator;

class ClusterWideBatchCommand extends SingleNodeBatchCommand implements RaftCommandInterface
{
    private bool $disableAtomicDocumentWrites = false;

    public function __construct(DocumentConventions $conventions, array $commands, BatchOptions $options, bool $disableAtomicDocumentsWrites)
    {
        parent::__construct($conventions, $commands, $options, TransactionMode::clusterWide());

        $this->disableAtomicDocumentWrites = $disableAtomicDocumentsWrites;
    }

    public function isDisableAtomicDocumentWrites(): bool {
        return $this->disableAtomicDocumentWrites;
    }

    public function getRaftUniqueRequestId(): string {
        return RaftIdGenerator::newId();
    }

//    @Override
//    protected void appendOptions(StringBuilder sb) {
//        super.appendOptions(sb);
//
//        if (_disableAtomicDocumentWrites == null) {
//            return;
//        }
//
//        sb
//                .append("&disableAtomicDocumentWrites=")
//                .append(_disableAtomicDocumentWrites ? "true" : "false");
//    }
}

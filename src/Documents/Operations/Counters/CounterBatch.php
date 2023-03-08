<?php

namespace RavenDB\Documents\Operations\Counters;

use Symfony\Component\Serializer\Annotation\SerializedName;

class CounterBatch
{
    #[SerializedName("ReplyWithAllNodesValues")]
    private bool $replyWithAllNodesValues = false;
    #[SerializedName("Documents")]
    private ?DocumentCountersOperationList $documents = null; //new ArrayList<>();
    #[SerializedName("FromEtl")]
    private bool $fromEtl = false;

    public function __construct()
    {
        $this->documents = new DocumentCountersOperationList();
    }

    public function isReplyWithAllNodesValues(): bool
    {
        return $this->replyWithAllNodesValues;
    }

    public function setReplyWithAllNodesValues(bool $replyWithAllNodesValues): void
    {
        $this->replyWithAllNodesValues = $replyWithAllNodesValues;
    }

    public function getDocuments(): ?DocumentCountersOperationList
    {
        return $this->documents;
    }

    public function setDocuments(null|DocumentCountersOperationList|array $documents): void
    {
        $this->documents = is_array($documents) ? DocumentCountersOperationList::fromArray($documents) : $documents;
    }

    public function isFromEtl(): bool
    {
        return $this->fromEtl;
    }

    public function setFromEtl(bool $fromEtl): void
    {
        $this->fromEtl = $fromEtl;
    }
}

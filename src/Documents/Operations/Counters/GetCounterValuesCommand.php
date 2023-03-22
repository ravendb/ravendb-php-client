<?php

namespace RavenDB\Documents\Operations\Counters;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Type\StringArray;
use RavenDB\Type\StringList;
use RavenDB\Utils\StringBuilder;
use RavenDB\Utils\UrlUtils;

class GetCounterValuesCommand extends RavenCommand
{
    private ?string $docId;
    private ?StringArray $counters = null;
    private bool $returnFullResults = false;
    private ?DocumentConventions $conventions = null;

    public function __construct(?string $docId, StringArray|array $counters, bool $returnFullResults, DocumentConventions $conventions)
    {
        parent::__construct(CountersDetail::class);

        if ($docId == null) {
            throw new IllegalArgumentException("DocId cannot be null");
        }

        $this->docId = $docId;
        $this->counters = $counters;
        $this->returnFullResults = $returnFullResults;
        $this->conventions = $conventions;
    }


    public function createUrl(ServerNode $serverNode): string
    {
        $request = $this->prepareRequest($serverNode);
        return $request->getUrl();
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return $this->prepareRequest($serverNode);
    }

    private function prepareRequest(ServerNode $serverNode): HttpRequest
    {
        $method = HttpRequest::GET;
        $options = [];

        $pathBuilder = new StringBuilder($serverNode->getUrl());
        $pathBuilder->append("/databases/")
            ->append($serverNode->getDatabase())
            ->append("/counters?docId=")
            ->append(UrlUtils::escapeDataString($this->docId));

        if (!empty($this->counters) && $this->counters->count()) {
            if (count($this->counters) > 1) {
                $options = $this->prepareRequestWithMultipleCounters($pathBuilder, $method);
            } else {
                $pathBuilder->append("&counter=")
                    ->append(UrlUtils::escapeDataString($this->counters[0]));
            }
        }

        if ($this->returnFullResults && $method == HttpRequest::GET) { // if we dropped to Post, _returnFullResults is part of the request content
            $pathBuilder->append("&full=true");
        }

        $url = $pathBuilder->__toString();

        return new HttpRequest($url, $method, $options);
    }

    private function prepareRequestWithMultipleCounters(?StringBuilder &$pathBuilder, string &$method): array
    {
        $options = [];
        $sumLengthRef = 0;
        $uniqueNames = $this->getOrderedUniqueNames($sumLengthRef);

        // if it is too big, we drop to POST (note that means that we can't use the HTTP cache any longer)
        // we are fine with that, such requests are going to be rare
        if ($sumLengthRef < 1024) {
            foreach ($uniqueNames as $uniqueName) {
                $pathBuilder->append("&counter=")
                    ->append(UrlUtils::escapeDataString($uniqueName ?? ""));
            }
        } else {
            $method = HttpRequest::POST;

            $docOps = new DocumentCountersOperation();
            $docOps->setDocumentId($this->docId);

            $operations = [];
            foreach ($uniqueNames as $counter) {
                $counterOperation = new CounterOperation();
                $counterOperation->setType(CounterOperationType::get());
                $counterOperation->setCounterName($counter);

                $operations[] = $counterOperation;
            }
            $docOps->setOperations($operations);

            $batch = new CounterBatch();
            $batch->setDocuments([$docOps]);
            $batch->setReplyWithAllNodesValues($this->returnFullResults);

            $options = [
                'json' => $this->getMapper()->normalize($batch),
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ];
        }

        return $options;
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            return;
        }

        $this->result = $this->getMapper()->deserialize($response, CountersDetail::class, 'json');
    }

    private function getOrderedUniqueNames(int &$sumRef): StringList
    {
        $orderedUniqueNames = new StringList();

        $sumRef = 0;

        foreach ($this->counters as $counter) {
            if (!$orderedUniqueNames->containsValue($counter)) {
                $orderedUniqueNames[] = $counter;
                $sumRef += $counter != null ? strlen($counter) : 0;
            }
        }

        return $orderedUniqueNames;
    }

    public function isReadRequest(): bool
    {
        return true;
    }

}

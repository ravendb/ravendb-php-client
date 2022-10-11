<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Queries\IndexQuery;
use RavenDB\Documents\Queries\QueryOperationOptions;
use RavenDB\Extensions\JsonExtensions;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Utils\TimeUtils;

class PatchByQueryCommand extends RavenCommand
{
    private ?DocumentConventions $conventions = null;
    private ?IndexQuery $queryToUpdate = null;
    private ?QueryOperationOptions $options = null;

    public function __construct(?DocumentConventions $conventions, ?IndexQuery $queryToUpdate, ?QueryOperationOptions $options)
    {
            parent::__construct(OperationIdResult::class);
            $this->conventions = $conventions;
            $this->queryToUpdate = $queryToUpdate;
            $this->options = $options ?? new QueryOperationOptions();
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        $path = $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/queries?allowStale="
                    . $this->options->isAllowStale();
            if ($this->options->getMaxOpsPerSecond() != null) {
                $path .= "&maxOpsPerSec=" . $this->options->getMaxOpsPerSecond();
            }

            $path .= "&details=" . $this->options->isRetrieveDetails();

            if ($this->options->getStaleTimeout() != null) {
                $path .= "&staleTimeout=" . TimeUtils::durationToTimeSpan($this->options->getStaleTimeout());
            }

            return $path;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $options = [
            'json'    => [
                'Query'          =>
                    JsonExtensions::writeIndexQuery($this->conventions, $this->queryToUpdate)
            ],
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ];

        return new HttpRequest($this->createUrl($serverNode), HttpRequest::PATCH, $options);
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            $this->throwInvalidResponse();
        }

        $this->result = $this->getMapper()->deserialize($response, OperationIdResult::class, 'json');
    }


}

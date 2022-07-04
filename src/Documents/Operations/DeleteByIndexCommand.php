<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Http\ServerNode;
use RavenDB\Utils\TimeUtils;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Extensions\JsonExtensions;
use RavenDB\Documents\Queries\IndexQuery;
use RavenDB\Documents\Queries\QueryOperationOptions;
use RavenDB\Documents\Conventions\DocumentConventions;

class DeleteByIndexCommand extends RavenCommand
{
    private ?DocumentConventions $conventions = null;
    private ?IndexQuery $queryToDelete = null;
    private ?QueryOperationOptions $options = null;

    public function __construct(?DocumentConventions $conventions, ?IndexQuery $queryToDelete, ?QueryOperationOptions $options)
    {
        parent::__construct(OperationIdResult::class);

        $this->conventions   = $conventions;
        $this->queryToDelete = $queryToDelete;
        $this->options       = $options ?? new QueryOperationOptions();
    }

    public function createUrl(ServerNode $serverNode): string
    {
        $url = $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/queries";
        $url .= "?allowStale=" . ($this->options->isAllowStale() ?? "");

        if ($this->options->getMaxOpsPerSecond() != null) {
            $url .= "&maxOpsPerSec=" . $this->options->getMaxOpsPerSecond();
        }

        $url .= "&details=" . ($this->options->isRetrieveDetails() ?? "");

        if ($this->options->getStaleTimeout() != null) {
            $url .= "&staleTimeout=" . TimeUtils::durationToTimeSpan($this->options->getStaleTimeout());
        }

        return $url;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $options = [
            'json' => JsonExtensions::writeIndexQuery($this->conventions, $this->queryToDelete),
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ];

        return new HttpRequest($this->createUrl($serverNode), HttpRequest::DELETE, $options);
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            self::throwInvalidResponse();
        }

        $this->result = $this->conventions->getEntityMapper()->deserialize($response, OperationIdResult::class, 'json');
    }

    public function isReadRequest(): bool
    {
        return false;
    }
}

<?php

namespace RavenDB\Documents\Operations\Indexes;

use RavenDB\Documents\Queries\TermsQueryResult;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Type\StringArrayResult;
use RavenDB\Utils\UrlUtils;

class GetTermsCommand extends RavenCommand
{
    private ?string $indexName = null;
    private ?string $field = null;
    private ?string $fromValue = null;
    private ?int $pageSize = null;

    public function __construct(?string $indexName, ?string $field, ?string $fromValue, ?int $pageSize = null)
    {
        parent::__construct(StringArrayResult::class);
        if ($indexName == null) {
            throw new IllegalArgumentException("IndexName cannot be null");
        }

        if ($field == null) {
            throw new IllegalArgumentException("Field cannot be null");
        }

        $this->indexName = $indexName;
        $this->field = $field;
        $this->fromValue = $fromValue;
        $this->pageSize = $pageSize;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase()
            . "/indexes/terms?name=" . UrlUtils::escapeDataString($this->indexName)
            . "&field=" . UrlUtils::escapeDataString($this->field)
            . "&fromValue=" . ($this->fromValue ?? "")
            . "&pageSize=" . ($this->pageSize ?? "");
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode), HttpRequest::GET);
    }

    public function setResponse(?string $response, bool $fromCache = false): void
    {
        if ($response == null) {
            $this->throwInvalidResponse();
        }

        $termResult = $this->getMapper()->deserialize($response, TermsQueryResult::class, 'json');

        $this->result = StringArrayResult::fromArray($termResult->getTerms()->getArrayCopy());
    }

    public function isReadRequest(): bool
    {
        return true;
    }
}

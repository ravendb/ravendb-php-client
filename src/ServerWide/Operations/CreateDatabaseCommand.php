<?php

namespace RavenDB\ServerWide\Operations;

use InvalidArgumentException;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\HeadersConstants;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RaftCommandInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\ServerWide\DatabaseRecord;
use RavenDB\ServerWide\Operations\DatabasePutResult;
use RavenDB\Utils\RaftIdGenerator;

class CreateDatabaseCommand extends RavenCommand implements RaftCommandInterface
{
    private DocumentConventions $conventions;
    private DatabaseRecord $databaseRecord;
    private int $replicationFactor;
    private ?int $eTag;
    private string $databaseName;

    public function __construct(
        DocumentConventions $conventions,
        DatabaseRecord $databaseRecord,
        int $replicationFactor,
        ?int $eTag = null
    ) {
        $this->conventions = $conventions;
        $this->databaseRecord = $databaseRecord;
        $this->replicationFactor = $replicationFactor;
        $this->eTag = $eTag;

        if (!$databaseRecord->getDatabaseName()) {
            throw new InvalidArgumentException('Database name is required');
        }
        $this->databaseName = $databaseRecord->getDatabaseName();

        parent::__construct(DatabasePutResult::class);
    }

    public function createUrl(ServerNode $serverNode): string
    {
        $url =  $serverNode->getUrl() . '/admin/databases?name=' . $this->databaseName;
        $url .= '&replicationFactor=' . $this->replicationFactor;

        return $url;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $databaseDocument = $this->getMapper()->normalize($this->databaseRecord, 'json');

        $options = [
            'json' => $databaseDocument,
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ];

        if ($this->eTag != null) {
            $options['headers'][HeadersConstants::ETAG] = "\"" . $this->eTag . "\"";
        }

        return new HttpRequest($this->createUrl($serverNode), HttpRequest::PUT, $options);
    }

    /**
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     * @throws \RavenDB\Exceptions\IllegalStateException
     */
    public function setResponse(?string $response, bool $fromCache): void
    {
        if (empty($response)) {
            $this->throwInvalidResponse();
        }

        $this->result = $this->getMapper()->deserialize($response, $this->getResultClass(), 'json');
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function getRaftUniqueRequestId(): string
    {
        return RaftIdGenerator::newId();
    }
}

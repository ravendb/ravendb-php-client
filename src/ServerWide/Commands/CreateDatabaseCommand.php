<?php

namespace RavenDB\ServerWide\Commands;

use InvalidArgumentException;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\HeadersConstants;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\ServerWide\DatabaseRecord;
use RavenDB\ServerWide\Operations\DatabasePutResult;

class CreateDatabaseCommand extends RavenCommand
{
    private DocumentConventions $conventions;
    private DatabaseRecord $databaseRecord;
    private int $replicationFactor;
    private ?int $eTag;
    private string $databaseName;

    public function __construct(DocumentConventions $conventions, DatabaseRecord $databaseRecord, int $replicationFactor, ?int $eTag = null)
    {
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

    protected function createUrl(ServerNode $serverNode): string
    {
        $url =  $serverNode->getUrl() . '/admin/databases?name=' . $this->databaseName;
        $url .= '&replicationFactor=' . $this->replicationFactor;

        return $url;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $databaseDocument = $this->getMapper()->serialize($this->databaseRecord, 'json');

        $options = [
            'json' => $databaseDocument,
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ];

        if ($this->eTag != null) {
            $options['headers'][HeadersConstants::ETAG] = $this->eTag;
        }

        return new HttpRequest($this->createUrl($serverNode), HttpRequest::PUT, $options);
    }

    public function isReadRequest(): bool
    {
        return false;
    }
}

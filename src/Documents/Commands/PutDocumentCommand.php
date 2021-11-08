<?php

namespace RavenDB\Documents\Commands;

use RavenDB\Documents\Commands\batches\PutResult;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use function PHPUnit\Framework\isEmpty;

class PutDocumentCommand extends RavenCommand
{

    private string $id;
    private ?string $changeVector;
    private array $document;

    /**
     * @throws IllegalArgumentException
     */
    public function __construct(string $id, ?string $changeVector, array $document)
    {
        parent::__construct(PutResult::class);

        if ($id == null) {
            throw new IllegalArgumentException("Id cannot be null");
        }

        if (($document == null) || empty($document)) {
            throw new IllegalArgumentException("Document cannot be null");
        }

        $this->id = $id;
        $this->changeVector = $changeVector;
        $this->document = $document;
    }

    protected function createUrl(ServerNode $serverNode): string
    {
        return  $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/docs?id=" . urlEncode($this->id);
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $options = [
            'json' => $this->document,
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ];

        return new HttpRequest($this->createUrl($serverNode), HttpRequest::PUT, $options);
    }

    public function isReadRequest(): bool
    {
        return false;
    }
}

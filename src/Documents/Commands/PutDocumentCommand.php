<?php

namespace RavenDB\Documents\Commands;

use InvalidArgumentException;
use RavenDB\Documents\Commands\Batches\PutResult;
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
     * @throws InvalidArgumentException
     */
    public function __construct(string $idOrCopy, ?string $changeVector, array $document)
    {
        parent::__construct(PutResult::class);

        if ($idOrCopy == null) {
            throw new InvalidArgumentException("Id cannot be null");
        }

        if (($document == null) || empty($document)) {
            throw new InvalidArgumentException("Document cannot be null");
        }

        $this->id = $idOrCopy;
        $this->changeVector = $changeVector;
        $this->document = $document;
    }

    public function createUrl(ServerNode $serverNode): string
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

    public function setResponse(?string $response, bool $fromCache): void
    {
        $this->setResult($this->getMapper()->deserialize($response, $this->getResultClass(), 'json'));
    }
}

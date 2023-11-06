<?php

namespace RavenDB\Documents\Commands;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\ServerNode;
use RavenDB\Http\VoidRavenCommand;

class DeleteDocumentCommand extends VoidRavenCommand
{
    private ?string $id = null;
    private ?string $changeVector = null;

    public function __construct(?string $idOrCopy, ?string $changeVector = null)
    {
        parent::__construct();

        if ($idOrCopy == null) {
            throw new IllegalArgumentException("Id cannot be null");
        }

        $this->id = $idOrCopy;
        $this->changeVector = $changeVector;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . '/databases/' . $serverNode->getDatabase() . '/docs?id=' . $this->urlEncode($this->id);
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        self::ensureIsNotNullOrString($this->id, "id");

        $request =  new HttpRequest($this->createUrl($serverNode), HttpRequest::DELETE);

        $this->addChangeVectorIfNotNull($this->changeVector, $request);

        return $request;
    }
}

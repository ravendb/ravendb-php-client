<?php

namespace RavenDB\Documents\Commands;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\BroadcastInterface;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RaftCommandInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Utils\RaftIdGenerator;

class NextIdentityForCommand extends RavenCommand implements RaftCommandInterface, BroadcastInterface
{
    private string $id;
    private string $raftUniqueRequestId;

    /**
     * @param string|NextIdentityForCommand|null $idOrCopy
     */
    public function __construct($idOrCopy)
    {
        parent::__construct();

        if (is_string($idOrCopy)) {
            $this->id = $idOrCopy;
            $this->raftUniqueRequestId = RaftIdGenerator::newId();
            return;
        }

        if ($idOrCopy instanceof NextIdentityForCommand) {
            $this->copyProperties($idOrCopy);
            return;
        }

        throw new IllegalArgumentException('Invalid parameter received. Parameter must be a string or instance of NextIdentityForCommand class.');
    }

    protected function copyProperties(RavenCommand $copy): void
    {
        parent::copyProperties($copy);

        if (! $copy instanceof NextIdentityForCommand) {
            throw new IllegalArgumentException('Parameter must be instance of NextIdentityForCommand class.');
        }

        $this->raftUniqueRequestId = $copy->raftUniqueRequestId;
        $this->id = $copy->id;
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/identity/next?name=" . urlEncode($this->id);
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $this->ensureIsNotNullOrString($this->id, "id");

        return new HttpRequest($this->createUrl($serverNode), HttpRequest::POST);
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            $this->throwInvalidResponse();
        }

        $decodedResponse = json_decode($response, true);
        if (!array_key_exists("NewIdentityValue", $decodedResponse)) {
            $this->throwInvalidResponse();
        }

        $this->result = intval($decodedResponse["NewIdentityValue"]);
    }

    public function getRaftUniqueRequestId(): string
    {
        return $this->raftUniqueRequestId;
    }

    public function prepareToBroadcast(?DocumentConventions $conventions): BroadcastInterface
    {
        return new NextIdentityForCommand($this);
    }
}

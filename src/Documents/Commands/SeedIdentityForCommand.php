<?php

namespace RavenDB\Documents\Commands;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RaftCommandInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Utils\RaftIdGenerator;

class SeedIdentityForCommand extends RavenCommand implements RaftCommandInterface
{
    private string $id;
    private int $value;
    private bool $forced;

    public function __construct(?string $id, int $value, bool $forced = false)
    {
        parent::__construct();
        if ($id == null) {
            throw new IllegalArgumentException("Id cannot be null");
        }

        $this->id = $id;
        $this->value = $value;
        $this->forced = $forced;
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        $url = $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/identity/seed?name=" . urlEncode($this->id) . "&value=" . $this->value;

        if ($this->forced) {
            $url .= "&force=true";
        }

        return $url;
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

        if (!array_key_exists("NewSeedValue", $decodedResponse)) {
            $this->throwInvalidResponse();
        }

        $this->result = intval($decodedResponse["NewSeedValue"]);
    }

    public function getRaftUniqueRequestId(): string
    {
        return RaftIdGenerator::newId();
    }
}

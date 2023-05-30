<?php

namespace RavenDB\Documents\Operations\ConnectionStrings;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RaftCommandInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Utils\RaftIdGenerator;
use function PHPUnit\Framework\throwException;

class PutConnectionStringCommand extends RavenCommand implements RaftCommandInterface
{
    private mixed $connectionString = null;

    public function __construct(mixed $connectionString = null)
    {
        parent::__construct(PutConnectionStringResult::class);

        if ($connectionString == null) {
            throw new IllegalArgumentException("ConnectionString cannot be null");
        }

        $this->connectionString = $connectionString;
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/admin/connection-strings";
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $options = [
            'json' => $this->getMapper()->normalize($this->connectionString),
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ];

        return new HttpRequest($this->createUrl($serverNode), HttpRequest::PUT, $options);
        }

        public function setResponse(?string $response, bool $fromCache): void
        {
            if ($response == null) {
                $this->throwInvalidResponse();
            }

            $this->result = $this->getMapper()->deserialize($response, $this->resultClass, 'json');
        }

        public function getRaftUniqueRequestId(): string
        {
            return RaftIdGenerator::newId();
        }
}

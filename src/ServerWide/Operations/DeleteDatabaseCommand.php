<?php

namespace RavenDB\ServerWide\Operations;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\RaftCommandInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\ServerWide\Operations\DeleteDatabaseCommandParameters;
use RavenDB\ServerWide\Operations\DeleteDatabaseResult;
use RavenDB\Utils\RaftIdGenerator;

class DeleteDatabaseCommand extends RavenCommand implements RaftCommandInterface
{
    private array $parameters = [];

    public function __construct(?DocumentConventions $conventions = null, ?DeleteDatabaseCommandParameters $parameters = null)
    {
        parent::__construct(DeleteDatabaseResult::class);

        if ($conventions == null) {
            throw new IllegalArgumentException("Conventions cannot be null");
        }

        if ($parameters == null) {
            throw new IllegalArgumentException("Parameters cannot be null");
        }

        // @todo: implement this
        try {
            $this->parameters = $this->getMapper()->normalize($parameters, 'json');
        } catch (\Throwable $e) {
//            echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
        }
//        } catch (JsonProcessingException $e) {
//            throw ExceptionsUtils::unwrapException($e);
//        }
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/admin/databases";
    }

    public function createRequest(ServerNode $serverNode): HttpRequest
    {
        $options = [
            'json' => $this->parameters,
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ];

        return new HttpRequest($this->createUrl($serverNode), HttpRequest::DELETE, $options);
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
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

<?php

namespace RavenDB\Http;

use RavenDB\Exceptions\InvalidResultAssignedToCommandException;
use RavenDB\Extensions\JsonExtensions;
use Symfony\Component\Serializer\Serializer;

abstract class RavenCommand
{
    protected ?ResultInterface $result = null;

    private ?string $resultClass;

    private Serializer $mapper;

    protected function __construct(?string $resultClass)
    {
        $this->resultClass = $resultClass;
        $this->mapper = JsonExtensions::getDefaultEntityMapper();
    }

    abstract protected function createUrl(ServerNode $serverNode): string;

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode));
    }

    public function getResult(): ?ResultInterface
    {
        return $this->result;
    }

    /**
     * @throws InvalidResultAssignedToCommandException
     */
    public function setResult(?ResultInterface $result): void
    {
        if (!$result instanceof $this->resultClass) {
            throw new InvalidResultAssignedToCommandException($this->resultClass);
        }

        $this->result = $result;
    }

    /**
     * @throws InvalidResultAssignedToCommandException
     */
    public function send(HttpClientInterface $client, HttpRequestInterface $request): HttpResponseInterface
    {
        $response = $client->execute($request);

        $this->setResult($this->getResultObject($response));

        return $response;
    }

    private function getResultObject(HttpResponseInterface $response)
    {
        return $this->getResultClass() ?
            $this->mapResultFromResponse($response) :
            $response;
    }

    private function mapResultFromResponse(HttpResponseInterface $response)
    {
        return $this->getMapper()->deserialize($response->getContent(), $this->getResultClass(), 'json');
    }

    public function getMapper(): Serializer
    {
        return $this->mapper;
    }

    public function setMapper(Serializer $mapper): void
    {
        $this->mapper = $mapper;
    }

    protected function getResultClass(): ?string
    {
        return $this->resultClass;
    }


}

<?php

namespace RavenDB\Http;

use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Exceptions\InvalidResultAssignedToCommandException;
use RavenDB\Extensions\JsonExtensions;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Serializer\Serializer;
use Throwable;

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
     * @throws ReflectionException
     */
    public function setResult(?ResultInterface $result): void
    {
        $reflectionClass = new ReflectionClass($this->resultClass);
        if (!$reflectionClass->isInstance($result)) {
            throw new InvalidResultAssignedToCommandException($this->resultClass);
        }

        $this->result = $result;
    }

    /**
     * @throws InvalidResultAssignedToCommandException|ReflectionException
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

    /**
     * @throws IllegalStateException
     */
    static protected function throwInvalidResponse(?Throwable $cause = null): void
    {
        $message = 'Response is invalid';
        if ($cause != null) {
            $message .= ': ' . $cause->getMessage();
        }
        throw new IllegalStateException($message);
    }
}

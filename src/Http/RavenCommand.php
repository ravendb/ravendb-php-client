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
    private ?string $resultClass;
    protected ?ResultInterface $result = null;

    protected int $statusCode = 0;
    protected RavenCommandResponseType $responseType;

//    protected Duration timeout;
    protected bool $canCache = false;
    protected bool $canCacheAggressively = false;
    protected ?string $selectedNodeTag = null;
    protected int $numberOfAttempts = 0;

    private Serializer $mapper;

    public int $failoverTopologyEtag = -2;

    abstract public function isReadRequest(): bool;

    protected function __construct(?string $resultClass)
    {
        $this->resultClass = $resultClass;
        $this->mapper = JsonExtensions::getDefaultEntityMapper();

        $this->responseType = RavenCommandResponseType::object();
        $this->canCache = true;
        $this->canCacheAggressively = true;
    }

    abstract protected function createUrl(ServerNode $serverNode): string;

    abstract public function createRequest(ServerNode $serverNode): HttpRequestInterface;
//    {
//        return new HttpRequest($this->createUrl($serverNode));
//    }

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

        $resultObject = $this->getResultObject($response);

        echo 'RESULT OBJECT' . PHP_EOL;
        print_r($resultObject);

        $this->setResult($resultObject);

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

//    protected function addChangeVectorIfNotNull(?string $changeVector, HttpRequest &$request): void
//    {
//        if ($changeVector != null) {
//            $request->addHeader("If-Match", "\"" . $changeVector . "\"");
//        }
//    }
//
//    public function onResponseFailure(CloseableHttpResponse $response): void
//    {
//
//    }
}

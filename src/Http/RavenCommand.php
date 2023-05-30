<?php

namespace RavenDB\Http;

use Ds\Map as DSMap;
use RavenDB\Constants\HttpStatusCode;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Exceptions\InvalidResultAssignedToCommandException;
use RavenDB\Exceptions\UnsupportedEncodingException;
use RavenDB\Exceptions\UnsupportedOperationException;
use RavenDB\Extensions\JsonExtensions;
use RavenDB\Type\Duration;
use RavenDB\Utils\HttpClientUtils;
use RavenDB\Utils\HttpUtils;
use RavenDB\Utils\StringUtils;
use RavenDB\Utils\UrlEncoder;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Serializer\Serializer;
use Throwable;

/**
 * @template T
 */
abstract class RavenCommand
{
    protected ?string $resultClass = null;

    /** @var ResultInterface|mixed|null  */
    protected mixed $result = null;

    protected int $statusCode = 0;
    protected ?RavenCommandResponseType $responseType = null;

    protected ?Duration $timeout = null;
    protected bool $canCache = false;
    protected bool $canCacheAggressively = false;
    protected ?string $selectedNodeTag = null;
    protected int $numberOfAttempts = 0;

    private Serializer $mapper;

    public int $failoverTopologyEtag = -2;

    abstract public function isReadRequest(): bool;

    public function getResponseType(): RavenCommandResponseType
    {
        return $this->responseType;
    }

    abstract public function createUrl(ServerNode $serverNode): string;

    abstract public function createRequest(ServerNode $serverNode): HttpRequestInterface;

    protected function __construct(?string $resultClass = null)
    {
        $this->resultClass = $resultClass;
        $this->mapper = JsonExtensions::getDefaultEntityMapper();

        $this->responseType = RavenCommandResponseType::object();
        $this->canCache = true;
        $this->canCacheAggressively = true;
    }

    protected function copyProperties(RavenCommand $copy): void
    {
        $this->canCache = $copy->canCache;
        $this->canCacheAggressively = $copy->canCacheAggressively;
        $this->selectedNodeTag = $copy->selectedNodeTag;
        $this->responseType = $copy->responseType;
    }

    public function getTimeout(): ?Duration
    {
        return $this->timeout;
    }

    public function setTimeout(?Duration $timeout): void
    {
        $this->timeout = $timeout;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    /**
     * @return ResultInterface|mixed|null
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param ResultInterface|mixed|null $result
     *
     * @throws InvalidResultAssignedToCommandException
     * @throws ReflectionException
     */
    public function setResult($result): void
    {
        if ($this->resultClass != null) {
            $reflectionClass = new ReflectionClass($this->resultClass);
            if (!$reflectionClass->isInstance($result)) {
                throw new InvalidResultAssignedToCommandException($this->resultClass);
            }
        }

        $this->result = $result;
    }

    public function canCache(): bool
    {
        return $this->canCache;
    }

    public function canCacheAggressively(): bool
    {
        return $this->canCacheAggressively;
    }

    public function getSelectedNodeTag(): ?string
    {
        return $this->selectedNodeTag;
    }

    public function getNumberOfAttempts(): int
    {
        return $this->numberOfAttempts;
    }

    public function setNumberOfAttempts(int $numberOfAttempts): void
    {
        $this->numberOfAttempts = $numberOfAttempts;
    }

    public function getResultClass(): ?string
    {
        return $this->resultClass;
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($this->responseType->isEmpty() || $this->responseType->isRaw()) {
            self::throwInvalidResponse();
        }

        throw new UnsupportedOperationException($this->responseType->getValue() . ' command must override the setResponse method which expects response with the following type: ' . $this->responseType->getValue());
    }

    public function send(HttpClientInterface $client, HttpRequestInterface $request): HttpResponseInterface
    {
        return $client->execute($request);
    }

    public function setResponseRaw(HttpResponseInterface $response): void
    {
        throw new UnsupportedOperationException('When ' . $this->responseType->getValue() . " is set to Raw then please override this method to handle the response.");
    }

    private ?DSMap $failedNodes = null;

    public function getFailedNodes(): ?DSMap
    {
        return $this->failedNodes;
    }

    public function setFailedNodes(?DSMap $failedNodes): void
    {
        $this->failedNodes = $failedNodes;
    }

    public function urlEncode(string $value): string
    {
        try {
            return UrlEncoder::encode($value);
        } catch (UnsupportedEncodingException $exception) {
            throw new \RuntimeException($exception);
        }
    }

    public static function ensureIsNotNullOrString(string $value, string $name): void
    {
        if (StringUtils::isEmpty($value)) {
            throw new IllegalArgumentException($name . ' cannot be null or empty');
        }
    }

    public function isFailedWithNode(ServerNode $node): bool
    {
        return ($this->failedNodes !== null) && $this->failedNodes->hasKey($node);
    }


    public function processResponse(?HttpCache $cache, ?HttpResponse $response, string $url): ResponseDisposeHandling
    {
        if (!$response) {
            return ResponseDisposeHandling::automatic();
        }

        if ($this->responseType->isEmpty() || ($response->getStatusCode() == HttpStatusCode::NO_CONTENT)) {
            return ResponseDisposeHandling::automatic();
        }

        try {
            if ($this->responseType->isObject()) {
                $content = $response->getContent();

                if (empty($content)) {
                    HttpClientUtils::closeQuietly($response);
                    return ResponseDisposeHandling::automatic();
                }

                // we intentionally don't dispose the reader here, we'll be using it
                // in the command, any associated memory will be released on context reset
                if ($cache != null) {
                    $this->cacheResponse($cache, $url, $response, $content);
                }
                $this->setResponse($content, false);
                return ResponseDisposeHandling::automatic();
            } else {
                $this->setResponseRaw($response);
            }
        } catch (Throwable $exception) {
            throw new \RuntimeException($exception);
        } finally {
            HttpClientUtils::closeQuietly($response);
        }

        return ResponseDisposeHandling::automatic();
    }

    protected function cacheResponse(HttpCache $cache, string $url, HttpResponse $response, string $responseJson): void
    {
        if (!$this->canCache()) {
            return;
        }

        $changeVector = HttpUtils::getEtagHeader($response);
        if ($changeVector == null) {
            return;
        }

        $cache->set($url, $changeVector, $responseJson);
    }

    /**
     * @throws IllegalStateException
     */
    protected static function throwInvalidResponse(?Throwable $cause = null): void
    {
        $message = 'Response is invalid';
        if ($cause != null) {
            $message .= ': ' . $cause->getMessage();
        }
        throw new IllegalStateException($message);
    }

    protected function addChangeVectorIfNotNull(?string $changeVector, HttpRequest &$request): void
    {
        if ($changeVector != null) {
            $request->addHeader("If-Match", "\"" . $changeVector . "\"");
        }
    }

    public function onResponseFailure(HttpResponse $response): void
    {
    }


    protected function mapResultFromResponse(HttpResponseInterface $response)
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


    private function getResultObject(HttpResponseInterface $response)
    {
        return $this->getResultClass() ?
            $this->mapResultFromResponse($response) :
            $response;
    }
}

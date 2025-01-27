<?php

namespace RavenDB\Documents\Session;

use InvalidArgumentException;
use RavenDB\Documents\DocumentStoreBase;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Http\CurrentIndexAndNode;
use RavenDB\Http\ReadBalanceBehavior;
use RavenDB\Http\RequestExecutor;
use RavenDB\Http\ServerNode;
use RavenDB\Utils\StringUtils;

class SessionInfo
{
    private int $sessionId = 0;
    private bool $sessionIdUsed = false;
    private int $loadBalancerContextSeed = 0;
    private bool $canUseLoadBalanceBehavior = false;
    private ?InMemoryDocumentSessionOperations $session = null;

    private int $lastClusterTransactionIndex = 0;
    private bool $noCaching = false;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        ?InMemoryDocumentSessionOperations $session,
        SessionOptions $options,
        ?DocumentStoreBase $documentStore
    ) {
        if ($documentStore === null) {
            throw new InvalidArgumentException("DocumentStore cannot be null");
        }
        if ($session == null) {
            throw new InvalidArgumentException("Session cannot be null");
        }

        $this->session = $session;
        $this->loadBalancerContextSeed = $session->getRequestExecutor()->getConventions()->getLoadBalancerContextSeed();
        $this->canUseLoadBalanceBehavior = $session->getConventions()->getLoadBalanceBehavior()->isUseSessionContext()
            && $session->getConventions()->getLoadBalancerPerSessionContextSelector() != null;

        $this->setLastClusterTransactionIndex($documentStore->getLastTransactionIndex($session->getDatabaseName()));
        $this->noCaching = $options->isNoCaching();
    }

    public function incrementRequestCount()
    {
        $this->session->incrementRequestCount();
    }

    public function setContext(?string $sessionKey): void
    {
        if (StringUtils::isBlank($sessionKey)) {
            throw new InvalidArgumentException("Session key cannot be null or whitespace.");
        }

        $this->setContextInternal($sessionKey);

        $this->canUseLoadBalanceBehavior =
            $this->canUseLoadBalanceBehavior
            || $this->session->getConventions()->getLoadBalanceBehavior()->isUseSessionContext();
    }

    private function setContextInternal(?string $sessionKey):  void
    {
        if ($this->sessionIdUsed) {
            throw new IllegalStateException(
                "Unable to set the session context after it has already been used. " .
                "The session context can only be modified before it is utilized."
            );
        }

//        if (sessionKey == null) {
//        Integer v = _clientSessionIdCounter.get();
//            _sessionId = ++v;
//            _clientSessionIdCounter.set(v);
//        } else {
//
//            byte[] sessionKeyBytes = sessionKey.getBytes();
//            byte[] bytesToHash = ByteBuffer
//              .allocate(sessionKeyBytes.length + 4)
//              .put(sessionKeyBytes)
//              .putInt(_loadBalancerContextSeed)
//              .array();
//            byte[] md5Bytes = DigestUtils.md5(bytesToHash);
//            _sessionId = ByteBuffer.wrap(md5Bytes)
//                .getInt();
//        }
    }


    public function getCurrentSessionNode(RequestExecutor $requestExecutor): ServerNode
    {
        if ($requestExecutor->getConventions()->getLoadBalanceBehavior()->isUseSessionContext()) {
            if ($this->canUseLoadBalanceBehavior) {
                $result = $requestExecutor->getNodeBySessionId($this->getSessionId());
                return $result->currentNode;
            }
        }

        switch ($requestExecutor->getConventions()->getReadBalanceBehavior()->getValue()) {
            case ReadBalanceBehavior::NONE:
                $result = $requestExecutor->getPreferredNode();
                break;
            case ReadBalanceBehavior::ROUND_ROBIN:
                $result = $requestExecutor->getNodeBySessionId($this->getSessionId());
                break;
            case ReadBalanceBehavior::FASTEST_NODE:
                $result = $requestExecutor->getFastestNode();
                break;
            default:
                throw new InvalidArgumentException(
                    $requestExecutor->getConventions()->getReadBalanceBehavior()->__toString()
                );
        }

        return $result->currentNode;
    }

    public function getSessionId(): int
    {
        if ($this->sessionId == null) {
            $context = null;
            $selector = $this->session->getConventions()->getLoadBalancerPerSessionContextSelector();
            if ($selector != null) {
                $context = $selector($this->session->getDatabaseName());
            }
            $this->setContextInternal($context);
        }
        $this->sessionIdUsed = true;
        return $this->sessionId;
    }

    public function canUseLoadBalanceBehavior(): bool
    {
        return $this->canUseLoadBalanceBehavior;
    }

    public function getLastClusterTransactionIndex(): int
    {
        return $this->lastClusterTransactionIndex;
    }

    public function setLastClusterTransactionIndex(int $lastClusterTransactionIndex): void
    {
        $this->lastClusterTransactionIndex = $lastClusterTransactionIndex;
    }

    public function isNoCaching(): bool
    {
        return $this->noCaching;
    }

    public function setNoCaching(bool $noCaching): void
    {
        $this->noCaching = $noCaching;
    }
}
